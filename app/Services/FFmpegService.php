<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FFmpegService
{
    protected $ffmpegPath = '/usr/bin/ffmpeg';
    protected $ffprobePath = '/usr/bin/ffprobe';
    protected $processes = [];
    protected $maxWorkers = 10;
    protected $workerTimeout = 300; // 5分钟

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 检查FFmpeg和FFprobe是否安装
        if (!file_exists($this->ffmpegPath)) {
            throw new \Exception('FFmpeg not found at ' . $this->ffmpegPath);
        }
        if (!file_exists($this->ffprobePath)) {
            throw new \Exception('FFprobe not found at ' . $this->ffprobePath);
        }
    }

    /**
     * 检查FFmpeg进程是否正在运行
     * 
     * @param int $channelId
     * @return bool
     */
    public function isRunning($channelId)
    {
        if (!isset($this->processes[$channelId])) {
            return false;
        }
        
        $process = $this->processes[$channelId];
        return $process->isRunning();
    }

    /**
     * 启动FFmpeg进程处理音频流
     * 
     * @param int $channelId
     * @param string $streamUrl
     * @return Process
     */
    public function startProcess($channelId, $streamUrl)
    {
        // 如果进程已经在运行，返回现有的进程
        if ($this->isRunning($channelId)) {
            return $this->processes[$channelId];
        }

        // 构建FFmpeg命令，转换为MP3格式以获得最佳浏览器兼容性
        $command = [
            $this->ffmpegPath,
            '-i', $streamUrl,
            '-c:a', 'libmp3lame',
            '-b:a', '128k',
            '-f', 'mp3',
            '-'  // 输出到标准输出
        ];

        // 启动进程
        $process = new Process($command);
        $process->setTimeout(null); // 无限超时
        $process->start(function ($type, $buffer) use ($channelId) {
            // 只记录错误输出，不记录音频流数据
            if ($type === Process::ERR) {
                Log::debug('FFmpeg Error [' . $channelId . ']: ' . $buffer);
            }
        });

        // 保存进程引用
        $this->processes[$channelId] = $process;

        Log::info('Started FFmpeg process for channel', [
            'channel_id' => $channelId,
            'stream_url' => $streamUrl
        ]);

        return $process;
    }

    /**
     * 停止FFmpeg进程
     * 
     * @param int $channelId
     * @return bool
     */
    public function stopProcess($channelId)
    {
        if (!$this->isRunning($channelId)) {
            return false;
        }

        $process = $this->processes[$channelId];
        $process->stop();

        unset($this->processes[$channelId]);

        Log::info('Stopped FFmpeg process for channel', [
            'channel_id' => $channelId
        ]);

        return true;
    }

    /**
     * 获取FFmpeg进程输出
     * 
     * @param int $channelId
     * @param int $maxLength
     * @return string
     */
    public function getOutput($channelId, $maxLength = 1024)
    {
        if (!$this->isRunning($channelId)) {
            return '';
        }

        $process = $this->processes[$channelId];
        return substr($process->getOutput(), 0, $maxLength);
    }

    /**
     * 处理音频流并返回
     * 
     * @param int $channelId
     * @param string $streamUrl
     * @param callable $callback
     * @return void
     */
    public function processStream($channelId, $streamUrl, callable $callback)
    {
        $maxRetries = 5;
        $retryCount = 0;
        $lastSuccessTime = time();
        $checkInterval = 1000; // 1ms检查间隔
        $maxSilentTime = 30; // 30秒无输出则重启，缩短超时时间

        try {
            while (true) { // 无限循环，持续处理
                try {
                    // 启动FFmpeg进程
                    $process = $this->startProcess($channelId, $streamUrl);
                    
                    // 重置重试计数
                    if ($retryCount > 0) {
                        Log::info('Successfully restarted FFmpeg process', [
                            'channel_id' => $channelId,
                            'retry_count' => $retryCount
                        ]);
                        $retryCount = 0;
                    }

                    // 持续监控进程输出
                    while (true) {
                        // 获取增量输出
                        $output = $process->getIncrementalOutput();
                        if (!empty($output)) {
                            // 将数据传递给回调函数
                            $stop = $callback($output);
                            // 如果回调返回false，停止处理
                            if ($stop === false) {
                                // 停止FFmpeg进程
                                $this->stopProcess($channelId);
                                return;
                            }
                            // 更新最后成功时间
                            $lastSuccessTime = time();
                        }
                        
                        // 检查错误输出
                        $errorOutput = $process->getIncrementalErrorOutput();
                        if (!empty($errorOutput)) {
                            Log::debug('FFmpeg Error [' . $channelId . ']: ' . $errorOutput);
                        }
                        
                        // 检查进程是否还在运行
                        if (!$process->isRunning()) {
                            // 进程已终止，尝试获取剩余输出
                            $remainingOutput = $process->getIncrementalOutput();
                            if (!empty($remainingOutput)) {
                                $callback($remainingOutput);
                                $lastSuccessTime = time();
                            }
                            
                            // 检查进程是否成功结束
                            if (!$process->isSuccessful()) {
                                Log::warning('FFmpeg process terminated unsuccessfully', [
                                    'channel_id' => $channelId,
                                    'exit_code' => $process->getExitCode(),
                                    'error_output' => $process->getErrorOutput()
                                ]);
                                throw new ProcessFailedException($process);
                            }
                            
                            Log::info('FFmpeg process completed successfully', [
                                'channel_id' => $channelId
                            ]);
                            break; // 进程正常结束，跳出内层循环准备重启
                        }
                        
                        // 确保进程有时间产生更多输出
                        usleep($checkInterval);
                        
                        // 检查是否超过指定时间没有输出
                        if (time() - $lastSuccessTime > $maxSilentTime) {
                            Log::warning('No output from FFmpeg for too long, restarting', [
                                'channel_id' => $channelId,
                                'silent_time' => time() - $lastSuccessTime
                            ]);
                            $this->stopProcess($channelId);
                            throw new \Exception('FFmpeg process timed out with no output');
                        }
                    }
                    
                    // 短暂休息后重新启动进程，避免过于频繁的重启
                    usleep(500000); // 500ms
                } catch (\Exception $e) {
                    Log::error('FFmpeg stream processing error, retrying...', [
                        'channel_id' => $channelId,
                        'stream_url' => $streamUrl,
                        'error' => $e->getMessage(),
                        'retry_count' => $retryCount
                    ]);
                    
                    // 增加重试计数
                    $retryCount++;
                    
                    // 如果超过最大重试次数，抛出异常
                    if ($retryCount > $maxRetries) {
                        Log::error('Max retries reached, giving up', [
                            'channel_id' => $channelId,
                            'max_retries' => $maxRetries
                        ]);
                        throw $e;
                    }
                    
                    // 等待一段时间后重试，随重试次数增加等待时间
                    $waitTime = min(5, $retryCount); // 最多等待5秒
                    sleep($waitTime);
                }
            }
        } catch (\Exception $e) {
            Log::error('FFmpeg stream processing failed', [
                'channel_id' => $channelId,
                'stream_url' => $streamUrl,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取音频流信息
     * 
     * @param string $streamUrl
     * @return array
     */
    public function getStreamInfo($streamUrl)
    {
        $command = [
            $this->ffprobePath,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $streamUrl
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return json_decode($process->getOutput(), true);
    }

    /**
     * 清理所有空闲进程
     * 
     * @return void
     */
    public function cleanupIdleProcesses()
    {
        foreach ($this->processes as $channelId => $process) {
            if (!$process->isRunning()) {
                unset($this->processes[$channelId]);
                Log::info('Cleaned up idle FFmpeg process', [
                    'channel_id' => $channelId
                ]);
            }
        }
    }

    /**
     * 停止所有FFmpeg进程
     * 
     * @return void
     */
    public function stopAllProcesses()
    {
        foreach (array_keys($this->processes) as $channelId) {
            $this->stopProcess($channelId);
        }
    }
}