<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PythonService
{
    protected $pythonPath = '/www/wwwroot/fm.liy.ink/audio-service/venv/bin/python';
    protected $gunicornPath = '/www/wwwroot/fm.liy.ink/audio-service/venv/bin/gunicorn';
    protected $audioServicePath = '/www/wwwroot/fm.liy.ink/audio-service';
    protected $pythonProcess = null;
    protected $processMonitor = null;
    protected $processCheckInterval = 60; // 60秒检查一次进程状态

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 检查Python和Gunicorn是否安装
        if (!file_exists($this->pythonPath)) {
            throw new \Exception('Python not found at ' . $this->pythonPath);
        }
        if (!file_exists($this->gunicornPath)) {
            throw new \Exception('Gunicorn not found at ' . $this->gunicornPath);
        }
        if (!file_exists($this->audioServicePath)) {
            throw new \Exception('Audio service path not found at ' . $this->audioServicePath);
        }
    }

    /**
     * 检查Python服务是否正在运行
     * 
     * @return bool
     */
    public function isPythonRunning()
    {
        // 检查是否有gunicorn进程运行
        // 直接传递命令数组，避免空格分割问题
        $command = ['pgrep', '-f', 'gunicorn --bind 0.0.0.0:5000'];
        $process = new Process($command);
        $process->run();
        
        return $process->getExitCode() === 0;
    }

    /**
     * 启动Python音频服务
     * 
     * @return bool
     */
    public function startPythonService()
    {
        // 如果服务已经在运行，返回true
        if ($this->isPythonRunning()) {
            Log::info('Python service is already running');
            return true;
        }

        // 启动Gunicorn服务
        $command = [
            $this->gunicornPath,
            '--bind', '0.0.0.0:5000',
            '--workers', '4',
            '--timeout', '3600',
            'app:app'
        ];

        $process = new Process($command, $this->audioServicePath);
        $process->setTimeout(null); // 无限超时
        $process->start(function ($type, $buffer) {
            if ($type === Process::ERR) {
                Log::debug('Python Service Error: ' . $buffer);
            } else {
                Log::debug('Python Service Output: ' . $buffer);
            }
        });

        // 等待服务启动
        sleep(2);

        if (!$this->isPythonRunning()) {
            Log::error('Failed to start Python service');
            return false;
        }

        Log::info('Started Python audio service successfully');
        return true;
    }

    /**
     * 停止Python音频服务
     * 
     * @return bool
     */
    public function stopPythonService()
    {
        // 停止所有gunicorn进程
        $command = 'pkill -f "gunicorn --bind 0.0.0.0:5000"';
        $process = new Process(explode(' ', $command));
        $process->run();

        // 等待进程停止
        sleep(2);

        if ($this->isPythonRunning()) {
            Log::error('Failed to stop Python service');
            return false;
        }

        Log::info('Stopped Python audio service successfully');
        return true;
    }

    /**
     * 重启Python音频服务
     * 
     * @return bool
     */
    public function restartPythonService()
    {
        $this->stopPythonService();
        return $this->startPythonService();
    }

    /**
     * 检查FFmpeg进程状态
     * 
     * @return array
     */
    public function checkFFmpegProcesses()
    {
        // 获取所有FFmpeg进程
        $command = 'ps aux';
        $process = new Process(explode(' ', $command));
        $process->run();
        
        $output = $process->getOutput();
        $processes = [];
        
        foreach (explode(PHP_EOL, $output) as $line) {
            if (!empty(trim($line)) && strpos($line, 'ffmpeg') !== false && strpos($line, 'grep') === false) {
                // 解析进程信息
                $parts = preg_split('/\s+/', $line, 11);
                if (count($parts) >= 11) {
                    $processes[] = [
                        'pid' => $parts[1],
                        'user' => $parts[0],
                        'cpu' => $parts[2],
                        'mem' => $parts[3],
                        'vsz' => $parts[4],
                        'rss' => $parts[5],
                        'tty' => $parts[6],
                        'stat' => $parts[7],
                        'start' => $parts[8],
                        'time' => $parts[9],
                        'command' => $parts[10],
                        // 尝试提取频道ID和流地址
                        'channel_id' => $this->extractChannelIdFromCommand($parts[10]),
                        'stream_url' => $this->extractStreamUrlFromCommand($parts[10]),
                        'start_time' => date('Y-m-d H:i:s', strtotime($parts[8]))
                    ];
                }
            }
        }
        
        Log::info('FFmpeg processes count: ' . count($processes));
        return $processes;
    }
    
    /**
     * 从命令行中提取频道ID
     * 
     * @param string $command
     * @return string
     */
    private function extractChannelIdFromCommand($command)
    {
        // 尝试从命令行中提取频道ID
        // 格式可能是：-i http://example.com/stream/123
        if (preg_match('/channel_id=(\d+)/i', $command, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\/(\d+)\//i', $command, $matches)) {
            return $matches[1];
        }
        return '未知';
    }
    
    /**
     * 从命令行中提取流地址
     * 
     * @param string $command
     * @return string
     */
    private function extractStreamUrlFromCommand($command)
    {
        // 尝试从命令行中提取流地址
        if (preg_match('/-i\s+(https?:\/\/[^\s]+)/i', $command, $matches)) {
            return $matches[1];
        }
        return '未知';
    }
    
    /**
     * 终止指定的FFmpeg进程
     * 
     * @param string $pid
     * @return bool
     */
    public function killFFmpegProcess($pid)
    {
        try {
            $command = "kill -9 {$pid}";
            $process = new Process(explode(' ', $command));
            $process->run();
            
            Log::info("Killed FFmpeg process with PID: {$pid}");
            return $process->getExitCode() === 0;
        } catch (\Exception $e) {
            Log::error("Failed to kill FFmpeg process with PID: {$pid}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 启动进程监控
     * 
     * @return void
     */
    public function startProcessMonitor()
    {
        if ($this->processMonitor) {
            Log::info('Process monitor is already running');
            return;
        }

        // 启动监控线程
        $this->processMonitor = new Process([
            $this->pythonPath, '-c', "
import time
import subprocess
import os

while True:
    # 检查Python服务
    try:
        result = subprocess.run(['pgrep', '-f', 'gunicorn --bind 0.0.0.0:5000'], capture_output=True, text=True)
        if result.returncode != 0:
            print('Python service not running, restarting...')
            # 重启Python服务
            subprocess.run(['/www/wwwroot/fm.liy.ink/audio-service/venv/bin/gunicorn', '--bind', '0.0.0.0:5000', '--workers', '4', '--timeout', '3600', 'app:app'], 
                         cwd='/www/wwwroot/fm.liy.ink/audio-service', 
                         stdout=subprocess.DEVNULL, 
                         stderr=subprocess.DEVNULL, 
                         background=True)
    except Exception as e:
        print(f'Error checking Python service: {e}')
    
    # 检查FFmpeg进程
    try:
        ffmpeg_processes = subprocess.run(['ps', 'aux'], capture_output=True, text=True)
        ffmpeg_count = len([line for line in ffmpeg_processes.stdout.splitlines() if 'ffmpeg' in line and 'grep' not in line])
        print(f'FFmpeg processes count: {ffmpeg_count}')
    except Exception as e:
        print(f'Error checking FFmpeg processes: {e}')
    
    time.sleep($this->processCheckInterval)
"
        ]);
        
        $this->processMonitor->setTimeout(null);
        $this->processMonitor->start(function ($type, $buffer) {
            Log::debug('Process Monitor: ' . $buffer);
        });
        
        Log::info('Started process monitor');
    }

    /**
     * 停止进程监控
     * 
     * @return void
     */
    public function stopProcessMonitor()
    {
        if ($this->processMonitor) {
            $this->processMonitor->stop();
            $this->processMonitor = null;
            Log::info('Stopped process monitor');
        }
    }

    /**
     * 获取Python服务状态
     * 
     * @return array
     */
    public function getPythonServiceStatus()
    {
        $isRunning = $this->isPythonRunning();
        $serviceUrl = 'http://0.0.0.0:5000';
        $monitorEnabled = $this->processMonitor && $this->processMonitor->isRunning();
        
        // 获取Python服务的PID
        $pid = null;
        if ($isRunning) {
            $command = 'pgrep -f "gunicorn --bind 0.0.0.0:5000"';
            $process = new Process(explode(' ', $command));
            $process->run();
            $pid = trim($process->getOutput());
        }
        
        return [
            'is_running' => $isRunning,
            'service_url' => $serviceUrl,
            'pid' => $pid,
            'start_time' => date('Y-m-d H:i:s'), // 简化处理，实际应该从进程信息中获取
            'monitor_enabled' => $monitorEnabled,
            'ffmpeg_processes_count' => count($this->checkFFmpegProcesses()),
            'monitor_running' => $monitorEnabled
        ];
    }

    /**
     * 自动修复所有服务
     * 
     * @return array
     */
    public function autoFixServices()
    {
        $results = [];
        
        // 检查并启动Python服务
        if (!$this->isPythonRunning()) {
            $results['python_restarted'] = $this->startPythonService();
        } else {
            $results['python_restarted'] = false;
        }
        
        // 检查并启动进程监控
        if (!$this->processMonitor || !$this->processMonitor->isRunning()) {
            $this->startProcessMonitor();
            $results['monitor_restarted'] = true;
        } else {
            $results['monitor_restarted'] = false;
        }
        
        $results['final_status'] = $this->getPythonServiceStatus();
        
        return $results;
    }
}
