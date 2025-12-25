<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PythonService;
use App\Services\FFmpegService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PythonServiceController extends Controller
{
    protected $pythonService;
    protected $ffmpegService;

    public function __construct(PythonService $pythonService, FFmpegService $ffmpegService)
    {
        $this->pythonService = $pythonService;
        $this->ffmpegService = $ffmpegService;
        // 确保只有管理员可以访问
        $this->middleware('auth:admin');
    }

    /**
     * 主页面 - Python服务管理控制台
     * 
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        try {
            $serviceStatus = $this->pythonService->getPythonServiceStatus();
            $ffmpegProcesses = $this->pythonService->checkFFmpegProcesses();
            
            return view('admin.python-service.index', [
                'serviceStatus' => $serviceStatus,
                'ffmpegProcesses' => $ffmpegProcesses
            ]);
        } catch (\Exception $e) {
            Log::error('加载Python服务管理页面失败', [
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => '加载页面失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取Python服务状态
     * 
     * @return JsonResponse
     */
    public function getStatus()
    {
        try {
            $status = $this->pythonService->getPythonServiceStatus();
            return $this->success($status);
        } catch (\Exception $e) {
            Log::error('获取Python服务状态失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('获取Python服务状态失败', 500);
        }
    }

    /**
     * 启动Python服务
     * 
     * @return JsonResponse
     */
    public function startPython()
    {
        try {
            $result = $this->pythonService->startPythonService();
            return $this->success([
                'success' => $result,
                'message' => $result ? 'Python服务启动成功' : 'Python服务已在运行'
            ]);
        } catch (\Exception $e) {
            Log::error('启动Python服务失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('启动Python服务失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 停止Python服务
     * 
     * @return JsonResponse
     */
    public function stopPython()
    {
        try {
            $result = $this->pythonService->stopPythonService();
            return $this->success([
                'success' => $result,
                'message' => $result ? 'Python服务停止成功' : 'Python服务未在运行'
            ]);
        } catch (\Exception $e) {
            Log::error('停止Python服务失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('停止Python服务失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 重启Python服务
     * 
     * @return JsonResponse
     */
    public function restartPython()
    {
        try {
            $result = $this->pythonService->restartPythonService();
            return $this->success([
                'success' => $result,
                'message' => $result ? 'Python服务重启成功' : 'Python服务重启失败'
            ]);
        } catch (\Exception $e) {
            Log::error('重启Python服务失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('重启Python服务失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 检查FFmpeg进程
     * 
     * @return JsonResponse
     */
    public function checkFFmpeg()
    {
        try {
            $processes = $this->pythonService->checkFFmpegProcesses();
            return $this->success([
                'count' => count($processes),
                'processes' => $processes
            ]);
        } catch (\Exception $e) {
            Log::error('检查FFmpeg进程失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('检查FFmpeg进程失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 启动进程监控
     * 
     * @return JsonResponse
     */
    public function startMonitor()
    {
        try {
            $this->pythonService->startProcessMonitor();
            return $this->success([
                'success' => true,
                'message' => '进程监控已启动'
            ]);
        } catch (\Exception $e) {
            Log::error('启动进程监控失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('启动进程监控失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 停止进程监控
     * 
     * @return JsonResponse
     */
    public function stopMonitor()
    {
        try {
            $this->pythonService->stopProcessMonitor();
            return $this->success([
                'success' => true,
                'message' => '进程监控已停止'
            ]);
        } catch (\Exception $e) {
            Log::error('停止进程监控失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('停止进程监控失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 自动修复所有服务
     * 
     * @return JsonResponse
     */
    public function autoFix()
    {
        try {
            $results = $this->pythonService->autoFixServices();
            return $this->success($results);
        } catch (\Exception $e) {
            Log::error('自动修复服务失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('自动修复服务失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取FFmpeg服务状态
     * 
     * @return JsonResponse
     */
    public function getFFmpegStatus()
    {
        try {
            $ffmpegProcesses = $this->pythonService->checkFFmpegProcesses();
            return $this->success([
                'processes' => $ffmpegProcesses,
                'count' => count($ffmpegProcesses)
            ]);
        } catch (\Exception $e) {
            Log::error('获取FFmpeg状态失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('获取FFmpeg状态失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 通用成功响应
     * 
     * @param mixed $data
     * @return JsonResponse
     */
    protected function success($data)
    {
        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $data
        ]);
    }

    /**
     * 通用错误响应
     * 
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function error($message, $code = 400)
    {
        return response()->json([
            'code' => $code,
            'message' => $message
        ], $code);
    }

    /**
     * 切换监控状态
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function monitor(Request $request)
    {
        try {
            $enabled = $request->input('enabled', false);
            
            if ($enabled) {
                $this->pythonService->startProcessMonitor();
                return $this->success([
                    'success' => true,
                    'message' => '进程监控已启用'
                ]);
            } else {
                $this->pythonService->stopProcessMonitor();
                return $this->success([
                    'success' => true,
                    'message' => '进程监控已禁用'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('切换监控状态失败', [
                'error' => $e->getMessage()
            ]);
            return $this->error('切换监控状态失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 终止FFmpeg进程
     * 
     * @param string $pid
     * @return JsonResponse
     */
    public function killFFmpeg($pid)
    {
        try {
            $result = $this->pythonService->killFFmpegProcess($pid);
            return $this->success([
                'success' => $result,
                'message' => $result ? 'FFmpeg进程终止成功' : 'FFmpeg进程终止失败'
            ]);
        } catch (\Exception $e) {
            Log::error('终止FFmpeg进程失败', [
                'pid' => $pid,
                'error' => $e->getMessage()
            ]);
            return $this->error('终止FFmpeg进程失败: ' . $e->getMessage(), 500);
        }
    }
}
