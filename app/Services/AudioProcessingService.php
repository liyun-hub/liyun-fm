<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 音频处理服务客户端
 * 负责与 Python 音频处理服务通信
 */
class AudioProcessingService
{
    protected $baseUrl;
    protected $timeout;
    protected $retryAttempts = 3;
    protected $retryDelay = 1; // 秒

    public function __construct()
    {
        $this->baseUrl = config('audio_service.url', 'http://localhost:5000');
        $this->timeout = config('audio_service.timeout', 30);
    }

    /**
     * 启动频道的 FFmpeg 进程
     * 
     * @param int $channelId
     * @param string $streamUrl
     * @return array
     * @throws \Exception
     */
    public function startProcess($channelId, $streamUrl)
    {
        $url = $this->baseUrl . "/api/process/{$channelId}/start";
        
        $data = [
            'stream_url' => $streamUrl
        ];

        Log::info('启动音频处理进程', [
            'channel_id' => $channelId,
            'stream_url' => $streamUrl,
            'api_url' => $url
        ]);

        $response = $this->makeRequest('POST', $url, $data);

        if ($response['code'] !== 200) {
            throw new \Exception("启动进程失败: {$response['message']}", $response['code']);
        }

        return $response['data'];
    }

    /**
     * 停止频道的 FFmpeg 进程
     * 
     * @param int $channelId
     * @return array
     * @throws \Exception
     */
    public function stopProcess($channelId)
    {
        $url = $this->baseUrl . "/api/process/{$channelId}/stop";

        Log::info('停止音频处理进程', [
            'channel_id' => $channelId,
            'api_url' => $url
        ]);

        $response = $this->makeRequest('POST', $url);

        if ($response['code'] !== 200 && $response['code'] !== 404) {
            throw new \Exception("停止进程失败: {$response['message']}", $response['code']);
        }

        return $response['data'] ?? [];
    }

    /**
     * 获取频道进程状态
     * 
     * @param int $channelId
     * @return array|null
     */
    public function getProcessStatus($channelId)
    {
        $url = $this->baseUrl . "/api/process/{$channelId}/status";

        try {
            $response = $this->makeRequest('GET', $url);
            
            if ($response['code'] === 200) {
                return $response['data'];
            } elseif ($response['code'] === 404) {
                return null; // 进程不存在
            } else {
                Log::warning('获取进程状态失败', [
                    'channel_id' => $channelId,
                    'response' => $response
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('获取进程状态异常', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 获取所有活跃进程列表
     * 
     * @return array
     */
    public function getAllProcesses()
    {
        $url = $this->baseUrl . "/api/processes";

        try {
            $response = $this->makeRequest('GET', $url);
            
            if ($response['code'] === 200) {
                return $response['data'];
            } else {
                Log::warning('获取进程列表失败', [
                    'response' => $response
                ]);
                return ['total' => 0, 'processes' => []];
            }
        } catch (\Exception $e) {
            Log::error('获取进程列表异常', [
                'error' => $e->getMessage()
            ]);
            return ['total' => 0, 'processes' => []];
        }
    }

    /**
     * 获取频道进程日志
     * 
     * @param int $channelId
     * @param int $lines
     * @return array
     */
    public function getProcessLogs($channelId, $lines = 100)
    {
        $url = $this->baseUrl . "/api/process/{$channelId}/logs";
        
        $params = ['lines' => $lines];

        try {
            $response = $this->makeRequest('GET', $url, $params);
            
            if ($response['code'] === 200) {
                return $response['data'];
            } else {
                Log::warning('获取进程日志失败', [
                    'channel_id' => $channelId,
                    'response' => $response
                ]);
                return ['channel_id' => $channelId, 'logs' => []];
            }
        } catch (\Exception $e) {
            Log::error('获取进程日志异常', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            return ['channel_id' => $channelId, 'logs' => []];
        }
    }

    /**
     * 检查音频处理服务是否可用
     * 
     * @return bool
     */
    public function isServiceAvailable()
    {
        $cacheKey = 'audio_service_health_check';
        
        // 使用缓存避免频繁检查
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = $this->baseUrl . "/api/processes";
            $response = Http::timeout(5)->get($url);
            
            $isAvailable = $response->successful();
            
            // 缓存结果 30 秒
            Cache::put($cacheKey, $isAvailable, now()->addSeconds(30));
            
            return $isAvailable;
        } catch (\Exception $e) {
            Log::warning('音频处理服务健康检查失败', [
                'error' => $e->getMessage()
            ]);
            
            // 缓存失败结果 10 秒
            Cache::put($cacheKey, false, now()->addSeconds(10));
            
            return false;
        }
    }

    /**
     * 获取 HLS 播放列表 URL
     * 
     * @param int $channelId
     * @return string
     */
    public function getHlsPlaylistUrl($channelId)
    {
        return $this->baseUrl . "/hls/{$channelId}/playlist.m3u8";
    }

    /**
     * 获取 HLS 切片 URL
     * 
     * @param int $channelId
     * @param string $segmentName
     * @return string
     */
    public function getHlsSegmentUrl($channelId, $segmentName)
    {
        return $this->baseUrl . "/hls/{$channelId}/{$segmentName}";
    }

    /**
     * 获取服务详细状态信息
     * 
     * @return array
     */
    public function getServiceStatus()
    {
        $url = $this->baseUrl . "/api/status";

        try {
            $response = $this->makeRequest('GET', $url);
            
            if ($response['code'] === 200) {
                return $response['data'];
            } else {
                Log::warning('获取服务状态失败', [
                    'response' => $response
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('获取服务状态异常', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 获取服务健康检查信息
     * 
     * @return array
     */
    public function getHealthCheck()
    {
        $url = $this->baseUrl . "/api/health";

        try {
            $response = $this->makeRequest('GET', $url);
            
            if ($response['code'] === 200) {
                return $response['data'];
            } else {
                Log::warning('获取健康检查失败', [
                    'response' => $response
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('获取健康检查异常', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 获取错误历史记录
     * 
     * @param int $minutes 获取最近多少分钟的错误
     * @return array
     */
    public function getErrorHistory($minutes = 60)
    {
        $url = $this->baseUrl . "/api/errors";
        $params = ['minutes' => $minutes];

        try {
            $response = $this->makeRequest('GET', $url, $params);
            
            if ($response['code'] === 200) {
                return $response['data'];
            } else {
                Log::warning('获取错误历史失败', [
                    'response' => $response
                ]);
                return ['recent_errors' => [], 'statistics' => []];
            }
        } catch (\Exception $e) {
            Log::error('获取错误历史异常', [
                'error' => $e->getMessage()
            ]);
            return ['recent_errors' => [], 'statistics' => []];
        }
    }

    /**
     * 触发错误恢复
     * 
     * @param int $channelId
     * @return array
     */
    public function triggerRecovery($channelId)
    {
        $url = $this->baseUrl . "/api/recovery/{$channelId}";

        try {
            $response = $this->makeRequest('POST', $url);
            
            if ($response['code'] === 200) {
                return $response['data'];
            } else {
                throw new \Exception("触发恢复失败: {$response['message']}", $response['code']);
            }
        } catch (\Exception $e) {
            Log::error('触发错误恢复异常', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 强制执行资源清理
     * 
     * @return array
     */
    public function forceCleanup()
    {
        $url = $this->baseUrl . "/api/cleanup";

        try {
            $response = $this->makeRequest('POST', $url);
            
            if ($response['code'] === 200) {
                return ['success' => true, 'message' => $response['message']];
            } else {
                throw new \Exception("强制清理失败: {$response['message']}", $response['code']);
            }
        } catch (\Exception $e) {
            Log::error('强制资源清理异常', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新进程活动时间
     * 
     * @param int $channelId
     * @return bool
     */
    public function updateActivityTime($channelId)
    {
        $url = $this->baseUrl . "/api/process/{$channelId}/activity";

        try {
            $response = $this->makeRequest('POST', $url);
            
            return $response['code'] === 200;
        } catch (\Exception $e) {
            Log::error('更新进程活动时间异常', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 获取Python服务进程信息
     * 
     * @return array|null
     */
    public function getPythonServiceInfo()
    {
        try {
            // 检查Python进程是否运行
            $command = "ps aux | grep 'python.*run.py' | grep -v grep";
            $output = shell_exec($command);
            
            if (empty($output)) {
                return [
                    'running' => false,
                    'pid' => null,
                    'start_time' => null,
                    'memory_usage' => null,
                    'cpu_usage' => null
                ];
            }
            
            // 解析进程信息
            $lines = explode("\n", trim($output));
            $processLine = trim($lines[0]);
            
            // 使用正则表达式解析ps输出
            if (preg_match('/^(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+/', $processLine, $matches)) {
                $user = $matches[1];
                $pid = $matches[2];
                $cpuUsage = $matches[3];
                $memUsage = $matches[4];
                
                // 获取进程启动时间
                $startTimeCommand = "ps -o lstart= -p {$pid} 2>/dev/null";
                $startTime = shell_exec($startTimeCommand);
                
                // 格式化启动时间为北京时间
                $formattedStartTime = 'Unknown';
                if ($startTime) {
                    try {
                        $startTime = trim($startTime);
                        
                        // 尝试使用 LANG=C 获取英文格式的时间
                        $englishTimeCommand = "LANG=C ps -o lstart= -p {$pid} 2>/dev/null";
                        $englishTime = shell_exec($englishTimeCommand);
                        
                        if ($englishTime) {
                            $englishTime = trim($englishTime);
                            // 英文格式: "Tue Dec 24 22:47:39 2025"
                            $carbonTime = \Carbon\Carbon::createFromFormat('D M j H:i:s Y', $englishTime);
                            $formattedStartTime = $carbonTime->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
                        } else {
                            // 如果无法获取英文格式，保持原始中文格式
                            $formattedStartTime = $startTime;
                        }
                    } catch (\Exception $e) {
                        // 如果解析失败，保持原始值
                        Log::warning('解析进程启动时间失败', [
                            'raw_time' => $startTime,
                            'error' => $e->getMessage()
                        ]);
                        $formattedStartTime = $startTime;
                    }
                }
                
                return [
                    'running' => true,
                    'pid' => (int)$pid,
                    'start_time' => $formattedStartTime,
                    'memory_usage' => $memUsage,
                    'cpu_usage' => $cpuUsage,
                    'user' => $user,
                    'command_line' => $processLine
                ];
            } else {
                // 如果解析失败，至少返回基本信息
                return [
                    'running' => true,
                    'pid' => null,
                    'start_time' => 'Unknown',
                    'memory_usage' => 'Unknown',
                    'cpu_usage' => 'Unknown',
                    'raw_output' => $processLine
                ];
            }
        } catch (\Exception $e) {
            Log::error('获取Python服务进程信息失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'running' => false,
                'error' => $e->getMessage(),
                'pid' => null,
                'start_time' => null,
                'memory_usage' => null,
                'cpu_usage' => null
            ];
        }
    }

    /**
     * 启动Python服务
     * 
     * @return array
     * @throws \Exception
     */
    public function startPythonService()
    {
        try {
            // 检查服务是否已在运行
            $serviceInfo = $this->getPythonServiceInfo();
            if ($serviceInfo && $serviceInfo['running']) {
                throw new \Exception('Python服务已在运行中');
            }
            
            // 启动Python服务
            $audioServicePath = base_path('audio-service');
            $command = "cd {$audioServicePath} && source venv/bin/activate && nohup python run.py > /dev/null 2>&1 & echo $!";
            
            $pid = shell_exec($command);
            $pid = trim($pid);
            
            if (!$pid || !is_numeric($pid)) {
                throw new \Exception('启动Python服务失败');
            }
            
            // 等待服务启动
            sleep(3);
            
            // 验证服务是否成功启动
            if (!$this->isServiceAvailable()) {
                throw new \Exception('Python服务启动后无法连接');
            }
            
            return [
                'success' => true,
                'pid' => (int)$pid,
                'message' => 'Python服务启动成功'
            ];
        } catch (\Exception $e) {
            Log::error('启动Python服务失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 停止Python服务
     * 
     * @return array
     * @throws \Exception
     */
    public function stopPythonService()
    {
        try {
            $serviceInfo = $this->getPythonServiceInfo();
            
            if (!$serviceInfo || !$serviceInfo['running']) {
                throw new \Exception('Python服务未在运行');
            }
            
            $pid = $serviceInfo['pid'];
            
            // 优雅地停止进程
            $result = shell_exec("kill -TERM {$pid} 2>&1");
            
            // 等待进程停止
            sleep(2);
            
            // 检查进程是否仍在运行
            $stillRunning = shell_exec("ps -p {$pid} -o pid= 2>/dev/null");
            
            if (!empty(trim($stillRunning))) {
                // 强制停止
                shell_exec("kill -KILL {$pid} 2>&1");
                sleep(1);
            }
            
            return [
                'success' => true,
                'message' => 'Python服务已停止'
            ];
        } catch (\Exception $e) {
            Log::error('停止Python服务失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 重启Python服务
     * 
     * @return array
     * @throws \Exception
     */
    public function restartPythonService()
    {
        try {
            Log::info('开始重启Python服务');
            
            // 先尝试停止服务
            try {
                $serviceInfo = $this->getPythonServiceInfo();
                if ($serviceInfo && $serviceInfo['running']) {
                    Log::info('检测到Python服务正在运行，先停止服务', ['pid' => $serviceInfo['pid']]);
                    $this->stopPythonService();
                    Log::info('Python服务已停止');
                } else {
                    Log::info('Python服务未运行，直接启动');
                }
            } catch (\Exception $e) {
                // 如果停止失败，记录警告但继续
                Log::warning('停止Python服务时出错，继续启动', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // 等待确保完全停止
            sleep(2);
            
            // 强制启动服务（绕过运行检查）
            $result = $this->forceStartPythonService();
            
            Log::info('Python服务重启成功', ['pid' => $result['pid']]);
            
            return [
                'success' => true,
                'message' => 'Python服务重启成功',
                'pid' => $result['pid']
            ];
        } catch (\Exception $e) {
            Log::error('重启Python服务失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 强制启动Python服务（不检查是否已运行）
     * 
     * @return array
     * @throws \Exception
     */
    protected function forceStartPythonService()
    {
        try {
            // 启动Python服务
            $audioServicePath = base_path('audio-service');
            
            // 验证路径和文件
            $activateScript = $audioServicePath . '/venv/bin/activate';
            $runScript = $audioServicePath . '/run.py';
            
            if (!file_exists($activateScript)) {
                throw new \Exception('虚拟环境不存在: ' . $activateScript);
            }
            
            if (!file_exists($runScript)) {
                throw new \Exception('运行脚本不存在: ' . $runScript);
            }
            
            // 使用系统临时目录创建启动脚本
            $tempDir = sys_get_temp_dir();
            $startScript = $tempDir . '/audio_service_start_' . uniqid() . '.sh';
            $scriptContent = "#!/bin/bash\ncd {$audioServicePath}\nsource venv/bin/activate\nnohup python run.py > /dev/null 2>&1 &\necho \$!";
            
            if (file_put_contents($startScript, $scriptContent) === false) {
                // 如果无法创建临时文件，使用直接命令方式
                Log::warning('无法创建临时启动脚本，使用直接命令方式');
                return $this->directStartPythonService();
            }
            
            chmod($startScript, 0755);
            
            Log::info('创建临时启动脚本', ['script' => $startScript]);
            
            // 执行启动脚本
            $output = shell_exec("bash {$startScript}");
            $pid = trim($output);
            
            Log::info('启动脚本执行结果', ['output' => $output, 'pid' => $pid]);
            
            // 清理临时脚本
            if (file_exists($startScript)) {
                unlink($startScript);
            }
            
            if (!$pid || !is_numeric($pid)) {
                throw new \Exception('启动Python服务失败，无法获取PID: ' . $output);
            }
            
            // 等待服务启动
            sleep(3);
            
            // 验证进程是否存在
            $processCheck = shell_exec("ps -p {$pid} -o pid= 2>/dev/null");
            if (empty(trim($processCheck))) {
                // 检查是否有其他Python run.py进程
                $pythonProcess = shell_exec("ps aux | grep 'python.*run.py' | grep -v grep | head -1");
                if (!empty($pythonProcess)) {
                    // 从进程信息中提取PID
                    if (preg_match('/^\S+\s+(\d+)/', trim($pythonProcess), $matches)) {
                        $pid = $matches[1];
                        Log::info('找到运行中的Python服务进程', ['pid' => $pid]);
                    } else {
                        throw new \Exception('Python服务进程启动失败或立即退出');
                    }
                } else {
                    throw new \Exception('Python服务进程启动失败或立即退出');
                }
            }
            
            Log::info('Python服务启动成功', ['pid' => $pid]);
            
            return [
                'success' => true,
                'pid' => (int)$pid,
                'message' => 'Python服务启动成功'
            ];
        } catch (\Exception $e) {
            Log::error('强制启动Python服务失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 直接启动Python服务（不使用临时脚本）
     * 
     * @return array
     * @throws \Exception
     */
    protected function directStartPythonService()
    {
        try {
            $audioServicePath = base_path('audio-service');
            
            // 使用更简单的命令，避免复杂的shell操作
            $pythonPath = $audioServicePath . '/venv/bin/python';
            $runScript = $audioServicePath . '/run.py';
            
            // 检查Python可执行文件
            if (!file_exists($pythonPath)) {
                throw new \Exception('Python可执行文件不存在: ' . $pythonPath);
            }
            
            // 使用绝对路径启动
            $command = "cd {$audioServicePath} && nohup {$pythonPath} {$runScript} > /dev/null 2>&1 & echo \$!";
            
            Log::info('使用直接命令启动Python服务', ['command' => $command]);
            
            $output = shell_exec($command);
            $pid = trim($output);
            
            Log::info('直接命令执行结果', ['output' => $output, 'pid' => $pid]);
            
            if (!$pid || !is_numeric($pid)) {
                throw new \Exception('启动Python服务失败，无法获取PID: ' . $output);
            }
            
            // 等待服务启动
            sleep(3);
            
            // 验证进程是否存在
            $processCheck = shell_exec("ps -p {$pid} -o pid= 2>/dev/null");
            if (empty(trim($processCheck))) {
                throw new \Exception('Python服务进程启动失败或立即退出');
            }
            
            Log::info('Python服务直接启动成功', ['pid' => $pid]);
            
            return [
                'success' => true,
                'pid' => (int)$pid,
                'message' => 'Python服务启动成功'
            ];
        } catch (\Exception $e) {
            Log::error('直接启动Python服务失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 发起 HTTP 请求
     * 
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function makeRequest($method, $url, $data = [])
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryAttempts) {
            try {
                $attempt++;

                $httpClient = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'User-Agent' => 'FM-Laravel-App/1.0'
                    ]);

                if ($method === 'GET') {
                    $response = $httpClient->get($url, $data);
                } elseif ($method === 'POST') {
                    $response = $httpClient->post($url, $data);
                } else {
                    throw new \Exception("不支持的 HTTP 方法: {$method}");
                }

                if ($response->successful()) {
                    $responseData = $response->json();
                    
                    // 验证响应格式
                    if (!isset($responseData['code']) || !isset($responseData['message'])) {
                        throw new \Exception('音频处理服务返回格式无效');
                    }
                    
                    return $responseData;
                } else {
                    // 尝试解析错误响应
                    $errorData = $response->json();
                    if (isset($errorData['code']) && isset($errorData['message'])) {
                        return $errorData;
                    } else {
                        throw new \Exception("HTTP 错误: {$response->status()} - {$response->body()}");
                    }
                }
            } catch (\Exception $e) {
                $lastException = $e;
                
                Log::warning('音频处理服务请求失败', [
                    'attempt' => $attempt,
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);

                if ($attempt < $this->retryAttempts) {
                    sleep($this->retryDelay);
                }
            }
        }

        // 所有重试都失败了
        throw new \Exception(
            "音频处理服务请求失败 (尝试 {$this->retryAttempts} 次): " . $lastException->getMessage(),
            500
        );
    }
}