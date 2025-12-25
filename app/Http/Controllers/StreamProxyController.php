<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class StreamProxyController extends Controller
{
    protected $cacheTtl = 60; // 缓存1分钟
    protected $bufferSize = 1024 * 1024; // 1MB缓冲区
    protected $cacheDuration = 15; // 缓存前15秒

    // 生成播放ID
    public function generatePlayId(Request $request)
    {
        $request->validate([
            'stream_url' => 'required|url'
        ]);

        $streamUrl = $request->stream_url;
        $playId = Str::random(32);

        // 缓存播放ID和对应的源URL
        Cache::put('stream:play_id:' . $playId, $streamUrl, now()->addMinutes(30));

        return response()->json([
            'play_id' => $playId,
            'proxy_url' => route('stream.proxy', ['playId' => $playId])
        ]);
    }

    // 代理播放流
    public function proxy(Request $request, $playId)
    {
        // 验证播放ID
        $streamUrl = Cache::get('stream:play_id:' . $playId);

        if (!$streamUrl) {
            return response()->json(['error' => '无效的播放ID'], 404);
        }

        // 检查是否已经缓存了前15秒
        $cacheKey = 'stream:cache:' . md5($streamUrl);
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            // 先返回缓存的前15秒
            return response($cachedData, 200, [
                'Content-Type' => 'audio/mpegurl',
                'Cache-Control' => 'no-cache',
                'Access-Control-Allow-Origin' => '*'
            ]);
        }

        // 否则从源获取并缓存
        try {
            $response = Http::withOptions([
                'stream' => true,
                'timeout' => 30
            ])->get($streamUrl);

            if (!$response->successful()) {
                return response()->json(['error' => '无法连接到源'], 500);
            }

            $contentType = $response->header('Content-Type', 'audio/mpegurl');
            $body = $response->body();

            // 缓存前15秒的数据
            $cacheData = substr($body, 0, $this->cacheDuration * 1024 * 1024); // 假设1MB/秒
            Cache::put($cacheKey, $cacheData, now()->addMinutes(5));

            return response($body, 200, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'no-cache',
                'Access-Control-Allow-Origin' => '*'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => '代理失败: ' . $e->getMessage()], 500);
        }
    }

    // Server Pool 管理
    public function serverPool()
    {
        // 可以在这里实现服务器池管理逻辑
        // 比如负载均衡、健康检查等
        return response()->json(['status' => 'ok']);
    }
}