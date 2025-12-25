<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AudioProcessingService;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 音频处理服务管理控制器
 */
class AudioProcessingController extends Controller
{
    protected $audioProcessingService;

    public function __construct(AudioProcessingService $audioProcessingService)
    {
        $this->audioProcessingService = $audioProcessingService;
    }

    /**
     * 显示音频处理服务管理页面
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            // 获取Python服务进程信息
            $pythonServiceInfo = null;
            try {
                $pythonServiceInfo = $this->audioProcessingService->getPythonServiceInfo();
            } catch (\Exception $e) {
                Log::error('获取Python服务信息失败', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $pythonServiceInfo = [
                    'running' => false,
                    'error' => $e->getMessage(),
                    'pid' => null,
                    'start_time' => null,
                    'memory_usage' => null,
                    'cpu_usage' => null
                ];
            }
            
            // 获取服务状态
            $isServiceAvailable = $this->audioProcessingService->isServiceAvailable();
            $serviceStatus = null;
            $healthCheck = null;
            $errorHistory = null;
            
            if ($isServiceAvailable) {
                // 获取详细服务状态
                $serviceStatus = $this->audioProcessingService->getServiceStatus();
                
                // 获取健康检查信息
                $healthCheck = $this->audioProcessingService->getHealthCheck();
                
                // 获取最近1小时的错误历史
                $errorHistory = $this->audioProcessingService->getErrorHistory(60);
            }
            
            // 获取所有活跃进程
            $processesData = $this->audioProcessingService->getAllProcesses();
            
            // 获取频道信息
            $channels = Channel::whereIn('id', collect($processesData['processes'])->pluck('channel_id'))
                ->get()
                ->keyBy('id');

            return view('admin.audio-processing.index', [
                'python_service_info' => $pythonServiceInfo,
                'service_available' => $isServiceAvailable,
                'service_status' => $serviceStatus,
                'health_check' => $healthCheck,
                'error_history' => $errorHistory,
                'processes_data' => $processesData,
                'channels' => $channels
            ]);
        } catch (\Exception $e) {
            Log::error('获取音频处理服务状态失败', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.audio-processing.index', [
                'python_service_info' => [
                    'running' => false,
                    'error' => $e->getMessage(),
                    'pid' => null,
                    'start_time' => null,
                    'memory_usage' => null,
                    'cpu_usage' => null
                ],
                'service_available' => false,
                'service_status' => null,
                'health_check' => null,
                'error_history' => null,
                'processes_data' => ['total' => 0, 'processes' => []],
                'channels' => collect(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取服务状态 (AJAX)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus()
    {
        try {
            $isServiceAvailable = $this->audioProcessingService->isServiceAvailable();
            $serviceStatus = null;
            $healthCheck = null;
            
            if ($isServiceAvailable) {
                $serviceStatus = $this->audioProcessingService->getServiceStatus();
                $healthCheck = $this->audioProcessingService->getHealthCheck();
            }
            
            $processesData = $this->audioProcessingService->getAllProcesses();
            
            return response()->json([
                'success' => true,
                'service_available' => $isServiceAvailable,
                'service_status' => $serviceStatus,
                'health_check' => $healthCheck,
                'processes_data' => $processesData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 启动频道进程
     * 
     * @param Request $request
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function startProcess(Request $request, $channelId)
    {
        try {
            $channel = Channel::findOrFail($channelId);
            
            $processData = $this->audioProcessingService->startProcess($channelId, $channel->stream_url);
            
            Log::info('管理员启动音频处理进程', [
                'channel_id' => $channelId,
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'process_data' => $processData
            ]);

            return response()->json([
                'success' => true,
                'message' => '进程启动成功',
                'data' => $processData
            ]);
        } catch (\Exception $e) {
            Log::error('管理员启动音频处理进程失败', [
                'channel_id' => $channelId,
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '启动进程失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 停止频道进程
     * 
     * @param Request $request
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopProcess(Request $request, $channelId)
    {
        try {
            $processData = $this->audioProcessingService->stopProcess($channelId);
            
            Log::info('管理员停止音频处理进程', [
                'channel_id' => $channelId,
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'process_data' => $processData
            ]);

            return response()->json([
                'success' => true,
                'message' => '进程停止成功',
                'data' => $processData
            ]);
        } catch (\Exception $e) {
            Log::error('管理员停止音频处理进程失败', [
                'channel_id' => $channelId,
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '停止进程失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取进程状态
     * 
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProcessStatus($channelId)
    {
        try {
            $processStatus = $this->audioProcessingService->getProcessStatus($channelId);
            
            return response()->json([
                'success' => true,
                'data' => $processStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取进程日志
     * 
     * @param Request $request
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProcessLogs(Request $request, $channelId)
    {
        try {
            $lines = $request->input('lines', 100);
            $logsData = $this->audioProcessingService->getProcessLogs($channelId, $lines);
            
            return response()->json([
                'success' => true,
                'data' => $logsData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 停止所有进程
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopAllProcesses(Request $request)
    {
        try {
            $processesData = $this->audioProcessingService->getAllProcesses();
            $stoppedCount = 0;
            $errors = [];

            foreach ($processesData['processes'] as $process) {
                try {
                    $this->audioProcessingService->stopProcess($process['channel_id']);
                    $stoppedCount++;
                } catch (\Exception $e) {
                    $errors[] = "频道 {$process['channel_id']}: " . $e->getMessage();
                }
            }

            Log::info('管理员停止所有音频处理进程', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'stopped_count' => $stoppedCount,
                'errors' => $errors
            ]);

            $message = "成功停止 {$stoppedCount} 个进程";
            if (!empty($errors)) {
                $message .= "，但有 " . count($errors) . " 个进程停止失败";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'stopped_count' => $stoppedCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            Log::error('管理员停止所有音频处理进程失败', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '停止所有进程失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取错误历史记录
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getErrorHistory(Request $request)
    {
        try {
            $minutes = $request->input('minutes', 60);
            $errorHistory = $this->audioProcessingService->getErrorHistory($minutes);
            
            return response()->json([
                'success' => true,
                'data' => $errorHistory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 触发错误恢复
     * 
     * @param Request $request
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function triggerRecovery(Request $request, $channelId)
    {
        try {
            $recoveryData = $this->audioProcessingService->triggerRecovery($channelId);
            
            Log::info('管理员触发错误恢复', [
                'channel_id' => $channelId,
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'recovery_data' => $recoveryData
            ]);

            return response()->json([
                'success' => true,
                'message' => '错误恢复已触发',
                'data' => $recoveryData
            ]);
        } catch (\Exception $e) {
            Log::error('管理员触发错误恢复失败', [
                'channel_id' => $channelId,
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '触发错误恢复失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 强制执行资源清理
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceCleanup(Request $request)
    {
        try {
            $cleanupResult = $this->audioProcessingService->forceCleanup();
            
            Log::info('管理员执行强制资源清理', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'cleanup_result' => $cleanupResult
            ]);

            return response()->json([
                'success' => true,
                'message' => '资源清理已执行',
                'data' => $cleanupResult
            ]);
        } catch (\Exception $e) {
            Log::error('管理员执行强制资源清理失败', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '强制资源清理失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新进程活动时间
     * 
     * @param Request $request
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActivityTime(Request $request, $channelId)
    {
        try {
            $success = $this->audioProcessingService->updateActivityTime($channelId);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => '活动时间已更新'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '更新活动时间失败'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新活动时间失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 启动Python服务
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startPythonService(Request $request)
    {
        try {
            $result = $this->audioProcessingService->startPythonService();
            
            Log::info('管理员启动Python服务', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('管理员启动Python服务失败', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '启动Python服务失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 停止Python服务
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopPythonService(Request $request)
    {
        try {
            $result = $this->audioProcessingService->stopPythonService();
            
            Log::info('管理员停止Python服务', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('管理员停止Python服务失败', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '停止Python服务失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 重启Python服务
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function restartPythonService(Request $request)
    {
        try {
            $result = $this->audioProcessingService->restartPythonService();
            
            Log::info('管理员重启Python服务', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('管理员重启Python服务失败', [
                'admin_user' => auth('admin')->user()->name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '重启Python服务失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取Python服务信息
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPythonServiceInfo()
    {
        try {
            $serviceInfo = $this->audioProcessingService->getPythonServiceInfo();
            
            return response()->json([
                'success' => true,
                'data' => $serviceInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}