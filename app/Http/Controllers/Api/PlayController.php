<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\AudioProcessingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlayController extends Controller
{
    protected $cacheTtl = 30; // 音频数据缓存30秒
    protected $urlTtl = 3600; // 播放链接有效期1小时
    protected $bufferSize = 1024 * 1024; // 1MB缓冲区
    
    // 签名密钥，建议从环境变量获取
    protected $signingKey;
    protected $audioProcessingService;

    public function __construct(AudioProcessingService $audioProcessingService)
    {
        $this->signingKey = config('app.key');
        $this->audioProcessingService = $audioProcessingService;
    }

    /**
     * 播放频道（getPlayUrl的别名，用于兼容前端调用）
     * 
     * @param Request $request
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function play(Request $request, $channelId)
    {
        try {
            // 验证频道是否存在
            $channel = Channel::findOrFail($channelId);
            
            // 检查频道是否激活和已审核
            if (!$channel->is_active || !$channel->is_approved) {
                return response()->json([
                'code' => 403,
                'message' => '频道不可用'
            ], 403);
            }

            // 检查音频处理服务是否可用
            if (!$this->audioProcessingService->isServiceAvailable()) {
                Log::error('音频处理服务不可用', [
                    'channel_id' => $channelId
                ]);
                return response()->json([
                'code' => 503,
                'message' => '音频处理服务暂时不可用，请稍后重试'
            ], 503);
            }

            // 预热：立即启动音频处理进程，不等待结果
            $this->preheatAudioProcess($channelId, $channel->stream_url);

            // 生成带签名的播放链接
            $timestamp = time();
            $signature = $this->generateSignature($channelId, $timestamp);
            
            // 构建HLS播放参数
            $params = [
                'channel_id' => $channelId,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'hls' => true
            ];
            
            // 生成HLS播放列表URL（始终返回转码后的HLS流）
            $hlsUrl = route('api.play.stream', $params);
            
            // 记录日志
            Log::info('生成HLS播放链接', [
                'channel_id' => $channelId,
                'channel_title' => $channel->title,
                'user_ip' => $request->ip(),
                'timestamp' => $timestamp
            ]);

            return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'play_url' => $hlsUrl,
                'channel_id' => $channelId,
                'channel_title' => $channel->title,
                'expires_at' => $timestamp + $this->urlTtl,
                'proxy_enabled' => $channel->proxy_enabled,
                'logo' => $channel->logo_url
            ]
        ]);
        } catch (\Exception $e) {
            Log::error('播放频道失败', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'code' => 500,
                'message' => '播放失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取频道播放链接
     * 
     * @param Request $request
     * @param int $channelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlayUrl(Request $request, $channelId)
    {
        try {
            // 验证频道是否存在
            $channel = Channel::findOrFail($channelId);
            
            // 检查频道是否激活和已审核
            if (!$channel->is_active || !$channel->is_approved) {
                return $this->error('频道不可用', 403);
            }

            // 检查音频处理服务是否可用
            if (!$this->audioProcessingService->isServiceAvailable()) {
                Log::error('音频处理服务不可用', [
                    'channel_id' => $channelId
                ]);
                return $this->error('音频处理服务暂时不可用，请稍后重试', 503);
            }

            // 预热：立即启动音频处理进程，不等待结果
            $this->preheatAudioProcess($channelId, $channel->stream_url);

            // 生成带签名的播放链接
            $timestamp = time();
            $signature = $this->generateSignature($channelId, $timestamp);
            
            // 构建播放参数
            $params = [
                'channel_id' => $channelId,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'hls' => true
            ];
            
            // 生成播放链接
            $playUrl = route('api.play.stream', $params);
            
            // 记录日志
            Log::info('生成播放链接', [
                'channel_id' => $channelId,
                'channel_title' => $channel->title,
                'user_ip' => $request->ip(),
                'timestamp' => $timestamp
            ]);

            return $this->success([
                'channel_id' => $channelId,
                'channel_title' => $channel->title,
                'play_url' => $playUrl,
                'expires_at' => $timestamp + $this->urlTtl,
                'proxy_enabled' => $channel->proxy_enabled,
                'logo' => $channel->logo_url
            ]);
        } catch (\Exception $e) {
            Log::error('获取播放链接失败', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            return $this->error('获取播放链接失败', 500);
        }
    }

    /**
     * 预热音频处理进程（异步启动，不阻塞响应）
     * 
     * @param int $channelId
     * @param string $streamUrl
     */
    protected function preheatAudioProcess($channelId, $streamUrl)
    {
        try {
            // 检查进程是否已在运行
            $processStatus = $this->audioProcessingService->getProcessStatus($channelId);
            if ($processStatus && $processStatus['status'] === 'running') {
                Log::info('音频处理进程已在运行', ['channel_id' => $channelId]);
                return;
            }

            // 异步启动进程（不等待结果）
            $this->audioProcessingService->startProcess($channelId, $streamUrl);
            
            Log::info('预热音频处理进程启动', ['channel_id' => $channelId]);
        } catch (\Exception $e) {
            // 预热失败不影响播放链接生成，只记录日志
            Log::warning('预热音频处理进程失败', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 代理播放流
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function stream(Request $request)
    {
        try {
            // 验证请求参数
            $channelId = $request->input('channel_id');
            $timestamp = $request->input('timestamp');
            $signature = $request->input('signature');

            // 参数验证
            if (!is_numeric($channelId) || !is_numeric($timestamp) || empty($signature)) {
                return $this->error('无效的请求参数', 400);
            }

            // 验证签名
            if (!$this->verifySignature((int)$channelId, (int)$timestamp, $signature)) {
                return $this->error('无效的播放链接', 401);
            }

            // 验证链接时效性
            if (time() - $timestamp > $this->urlTtl) {
                return $this->error('播放链接已过期', 401);
            }

            // 获取频道信息
            $channel = Channel::findOrFail($channelId);

            // 检查是否请求的是HLS播放列表或切片
            $useHls = $request->input('hls', false);
            $segmentName = $request->input('segment', false);
            
            if ($useHls) {
                return $this->handleHlsRequest($channelId, $channel, $segmentName, $timestamp, $signature);
            }

            // 传统MP3流模式 - 直接重定向到音频处理服务
            return $this->handleMp3Stream($channelId, $channel);

        } catch (\Exception $e) {
            Log::error('代理播放流失败', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return $this->error('播放失败', 500);
        }
    }

    /**
     * 处理 HLS 请求
     * 
     * @param int $channelId
     * @param Channel $channel
     * @param string|false $segmentName
     * @param int $timestamp
     * @param string $signature
     * @return \Illuminate\Http\Response
     */
    protected function handleHlsRequest($channelId, $channel, $segmentName, $timestamp, $signature)
    {
        if ($segmentName) {
            // 请求 TS 切片
            return $this->proxyHlsSegment($channelId, $segmentName);
        } else {
            // 请求播放列表
            return $this->proxyHlsPlaylist($channelId, $channel, $timestamp, $signature);
        }
    }

    /**
     * 代理 HLS 播放列表
     * 
     * @param int $channelId
     * @param Channel $channel
     * @param int $timestamp
     * @param string $signature
     * @return \Illuminate\Http\Response
     */
    protected function proxyHlsPlaylist($channelId, $channel, $timestamp, $signature)
    {
        try {
            // 确保进程正在运行
            $this->ensureProcessRunning($channelId, $channel->stream_url);

            // 检查播放列表缓存（缓存时间更短）
            $cacheKey = "hls_playlist:{$channelId}";
            $cachedPlaylist = Cache::get($cacheKey);
            
            if ($cachedPlaylist) {
                return response($cachedPlaylist, 200, [
                    'Content-Type' => 'application/vnd.apple.mpegurl',
                    'Cache-Control' => 'max-age=3, must-revalidate',  # 允许3秒缓存
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                    'X-Cache-Status' => 'HIT'
                ]);
            }

            // 获取音频处理服务的播放列表
            $hlsPlaylistUrl = $this->audioProcessingService->getHlsPlaylistUrl($channelId);
            
            Log::info('请求 HLS 播放列表', [
                'channel_id' => $channelId,
                'playlist_url' => $hlsPlaylistUrl
            ]);

            $response = Http::timeout(2)->get($hlsPlaylistUrl);  // 减少超时时间到2秒
            
            if ($response->successful()) {
                // 修改播放列表中的TS切片URL为PHP代理地址
                $playlistContent = $response->body();
                $proxyBaseUrl = route('api.play.stream', [
                    'channel_id' => $channelId,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'hls' => true
                ]);
                
                // 替换所有TS切片URL为PHP代理地址
                $playlistContent = preg_replace(
                    '/([a-zA-Z0-9_-]+\.ts)/',
                    $proxyBaseUrl . '&segment=$1',
                    $playlistContent
                );
                
                // 缓存播放列表2秒（增加缓存时间，减少请求频率）
                Cache::put($cacheKey, $playlistContent, now()->addSeconds(2));
                
                return response($playlistContent, 200, [
                    'Content-Type' => 'application/vnd.apple.mpegurl',
                    'Cache-Control' => 'max-age=3, must-revalidate',  # 允许3秒缓存
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                    'X-Cache-Status' => 'MISS'
                ]);
            } elseif ($response->status() == 404) {
                // 播放列表尚未生成，快速重试机制
                return $this->handlePlaylistNotReady($channelId, $channel, $timestamp, $signature);
            } else {
                Log::error('获取HLS播放列表失败', [
                    'channel_id' => $channelId,
                    'url' => $hlsPlaylistUrl,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return $this->error('获取播放列表失败', 500);
            }
        } catch (\Exception $e) {
            Log::error('代理HLS播放列表异常', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            return $this->error('获取播放列表失败', 500);
        }
    }

    /**
     * 确保音频处理进程正在运行
     * 
     * @param int $channelId
     * @param string $streamUrl
     */
    protected function ensureProcessRunning($channelId, $streamUrl)
    {
        try {
            $processStatus = $this->audioProcessingService->getProcessStatus($channelId);
            
            if (!$processStatus || $processStatus['status'] !== 'running') {
                Log::info('进程未运行，立即启动', ['channel_id' => $channelId]);
                $this->audioProcessingService->startProcess($channelId, $streamUrl);
                
                // 短暂等待进程启动
                usleep(500000); // 0.5秒
            }
        } catch (\Exception $e) {
            Log::warning('确保进程运行失败', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 处理播放列表未就绪的情况
     * 
     * @param int $channelId
     * @param Channel $channel
     * @param int $timestamp
     * @param string $signature
     * @return \Illuminate\Http\Response
     */
    protected function handlePlaylistNotReady($channelId, $channel, $timestamp, $signature)
    {
        // 快速重试一次
        usleep(200000); // 等待0.2秒
        
        $hlsPlaylistUrl = $this->audioProcessingService->getHlsPlaylistUrl($channelId);
        $response = Http::timeout(1)->get($hlsPlaylistUrl);
        
        if ($response->successful()) {
            // 成功获取，处理播放列表
            $playlistContent = $response->body();
            $proxyBaseUrl = route('api.play.stream', [
                'channel_id' => $channelId,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'hls' => true
            ]);
            
            $playlistContent = preg_replace(
                '/([a-zA-Z0-9_-]+\.ts)/',
                $proxyBaseUrl . '&segment=$1',
                $playlistContent
            );
            
            return response($playlistContent, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Cache-Control' => 'max-age=3, must-revalidate',  # 允许3秒缓存
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                'X-Cache-Status' => 'RETRY-SUCCESS'
            ]);
        }
        
        // 仍未就绪，返回202状态
        return response()->json([
            'code' => 202,
            'message' => '正在加载音频流，请稍候重试',
            'retry_after' => 0.2,  // 进一步减少重试间隔到0.2秒
            'channel_id' => $channelId
        ], 202, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
        ]);
    }

    /**
     * 代理 HLS 切片
     * 
     * @param int $channelId
     * @param string $segmentName
     * @return \Illuminate\Http\Response
     */
    protected function proxyHlsSegment($channelId, $segmentName)
    {
        try {
            // 检查切片是否已缓存
            $cacheKey = "hls_segment:{$channelId}:{$segmentName}";
            $cachedSegment = Cache::get($cacheKey);
            
            if ($cachedSegment) {
                return response($cachedSegment, 200, [
                    'Content-Type' => 'video/MP2T',
                    'Cache-Control' => 'public, max-age=60',  // 增加缓存时间到60秒
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                    'X-Cache-Status' => 'HIT'
                ]);
            }

            // 从音频处理服务获取切片
            $segmentUrl = $this->audioProcessingService->getHlsSegmentUrl($channelId, $segmentName);
            
            Log::debug('请求 HLS 切片', [
                'channel_id' => $channelId,
                'segment_name' => $segmentName,
                'segment_url' => $segmentUrl
            ]);

            $response = Http::timeout(2)->get($segmentUrl);  // 减少超时时间到2秒
            
            if ($response->successful()) {
                $segmentData = $response->body();
                
                // 缓存切片数据60秒（增加缓存时间，减少重复请求）
                Cache::put($cacheKey, $segmentData, now()->addSeconds(60));
                
                return response($segmentData, 200, [
                    'Content-Type' => 'video/MP2T',
                    'Cache-Control' => 'public, max-age=60',  # 增加缓存时间到60秒
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
                    'X-Cache-Status' => 'MISS'
                ]);
            } else {
                Log::error('获取HLS切片失败', [
                    'channel_id' => $channelId,
                    'segment_name' => $segmentName,
                    'status' => $response->status()
                ]);
                return $this->error('获取切片失败', 404);
            }
        } catch (\Exception $e) {
            Log::error('代理HLS切片异常', [
                'channel_id' => $channelId,
                'segment_name' => $segmentName,
                'error' => $e->getMessage()
            ]);
            return $this->error('获取切片失败', 500);
        }
    }

    /**
     * 处理 MP3 流
     * 
     * @param int $channelId
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    protected function handleMp3Stream($channelId, $channel)
    {
        try {
            // 检查进程状态
            $processStatus = $this->audioProcessingService->getProcessStatus($channelId);
            
            if (!$processStatus || $processStatus['status'] !== 'running') {
                // 尝试启动进程
                try {
                    $this->audioProcessingService->startProcess($channelId, $channel->stream_url);
                    // 等待进程启动
                    sleep(2);
                } catch (\Exception $e) {
                    Log::error('启动音频处理进程失败', [
                        'channel_id' => $channelId,
                        'error' => $e->getMessage()
                    ]);
                    return $this->error('音频处理服务启动失败', 500);
                }
            }

            // 构建音频处理服务的流URL
            $streamUrl = config('audio_service.url') . "/stream/{$channelId}";
            
            Log::info('重定向到音频处理服务', [
                'channel_id' => $channelId,
                'stream_url' => $streamUrl
            ]);

            // 直接重定向到音频处理服务
            return redirect()->to($streamUrl);
            
        } catch (\Exception $e) {
            Log::error('处理MP3流失败', [
                'channel_id' => $channelId,
                'error' => $e->getMessage()
            ]);
            return $this->error('音频流处理失败', 500);
        }
    }

    /**
     * 生成签名
     * 
     * @param int $channelId
     * @param int $timestamp
     * @return string
     */
    protected function generateSignature($channelId, $timestamp)
    {
        $data = "{$channelId}:{$timestamp}";
        return hash_hmac('sha256', $data, $this->signingKey);
    }

    /**
     * 验证签名
     * 
     * @param int $channelId
     * @param int $timestamp
     * @param string $signature
     * @return bool
     */
    protected function verifySignature($channelId, $timestamp, $signature)
    {
        $expectedSignature = $this->generateSignature($channelId, $timestamp);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * 处理TS分片文件请求
     * 
     * @param Request $request
     * @param string $segment
     * @return \Illuminate\Http\Response
     */
    public function streamTS(Request $request, $segment)
    {
        try {
            // 获取当前活动的频道信息（从请求参数或会话中获取）
            $channelId = $request->input('channel_id');
            $timestamp = $request->input('timestamp');
            $signature = $request->input('signature');
            
            // 参数验证
            if (!is_numeric($channelId) || !is_numeric($timestamp) || empty($signature)) {
                return $this->error('无效的请求参数', 400);
            }
            
            // 验证签名和时效性
            if (!$this->verifySignature((int)$channelId, (int)$timestamp, $signature)) {
                return $this->error('无效的播放链接', 401);
            }
            
            if (time() - $timestamp > $this->urlTtl) {
                return $this->error('播放链接已过期', 401);
            }
            
            // 代理到音频处理服务
            return $this->proxyHlsSegment($channelId, $segment);
            
        } catch (\Exception $e) {
            Log::error('播放TS分片失败', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return $this->error('播放失败', 500);
        }
    }

    /**
     * 成功响应格式
     * 
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
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
     * 错误响应格式
     * 
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($message, $code = 400)
    {
        return response()->json([
            'code' => $code,
            'message' => $message
        ], $code);
    }
}
