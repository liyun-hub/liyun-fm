# FM广播播放代理API文档

## 1. 接口概述

本API提供了安全的音频流播放与代理功能，包含以下核心特性：

- 基于频道ID的播放链接生成
- 播放链接时效性控制（默认1小时）
- 签名验证机制防止链接篡改
- 音频数据缓存（30秒）减轻源服务器负载
- 支持代理模式和直接访问模式

## 2. 接口列表

| 接口名称 | URL | 请求方法 | 功能描述 |
|---------|-----|---------|---------|
| 获取播放链接 | /api/play/{channelId} | GET | 根据频道ID获取带签名的播放链接 |
| 音频流代理 | /api/stream | GET | 提供音频流代理服务 |

## 3. 获取播放链接接口

### 3.1 接口URL

```
GET /api/play/{channelId}
```

### 3.2 请求参数

| 参数名 | 类型 | 位置 | 必须 | 描述 |
|-------|------|------|------|------|
| channelId | int | URL路径 | 是 | 频道ID |

### 3.3 响应格式

#### 成功响应

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "channel_id": 1,
    "channel_title": "CNR-3 音乐之声",
    "play_url": "http://example.com/api/stream?channel_id=1&timestamp=1678901234&signature=abc123def456",
    "expires_at": 1678904834,
    "proxy_enabled": true
  }
}
```

#### 失败响应

```json
{
  "code": 403,
  "message": "频道不可用"
}
```

```json
{
  "code": 404,
  "message": "频道不存在"
}
```

```json
{
  "code": 500,
  "message": "获取播放链接失败"
}
```

## 4. 音频流代理接口

### 4.1 接口URL

```
GET /api/stream
```

### 4.2 请求参数

| 参数名 | 类型 | 位置 | 必须 | 描述 |
|-------|------|------|------|------|
| channel_id | int | Query | 是 | 频道ID |
| timestamp | int | Query | 是 | 请求时间戳 |
| signature | string | Query | 是 | 签名信息 |

### 4.3 签名生成算法

签名使用HMAC-SHA256算法生成，计算公式如下：

```
signature = HMAC_SHA256("{channelId}:{timestamp}", signingKey)
```

其中：
- `channelId`: 频道ID
- `timestamp`: 当前Unix时间戳
- `signingKey`: 服务器签名密钥（使用Laravel的APP_KEY）

### 4.4 响应格式

#### 成功响应

返回音频流数据，Content-Type根据实际音频格式设置，常见值：
- `audio/mpeg` - MP3格式
- `application/vnd.apple.mpegurl` - HLS(m3u8)格式

#### 失败响应

```json
{
  "code": 401,
  "message": "无效的播放链接"
}
```

```json
{
  "code": 401,
  "message": "播放链接已过期"
}
```

```json
{
  "code": 500,
  "message": "获取音频流失败"
}
```

## 5. 错误码列表

| 错误码 | 描述 | HTTP状态码 |
|-------|------|-----------|
| 200 | 成功 | 200 |
| 400 | 参数错误 | 400 |
| 401 | 未授权/签名错误/链接过期 | 401 |
| 403 | 频道不可用 | 403 |
| 404 | 频道不存在/资源未找到 | 404 |
| 500 | 服务器内部错误 | 500 |

## 6. 安全机制

### 6.1 链接时效性

- 播放链接默认有效期为1小时
- 超时后需要重新获取链接
- 时间戳使用Unix时间格式

### 6.2 签名验证

- 确保请求参数未被篡改
- 使用HMAC-SHA256算法
- 密钥从服务器环境变量获取

### 6.3 代理模式

- 当频道启用代理模式时，原始音频源URL不会暴露给用户
- 所有音频流通过服务器代理传输
- 支持音频数据缓存（30秒）

## 7. 前端集成示例

```javascript
async function playChannel(channelId) {
    try {
        const response = await fetch(`/api/play/${channelId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (!response.ok) {
            throw new Error('获取播放链接失败');
        }
        
        const data = await response.json();
        
        if (data.code !== 200) {
            throw new Error(data.message);
        }
        
        // 使用获取到的播放链接进行播放
        const audioElement = document.getElementById('audio-player');
        audioElement.src = data.data.play_url;
        audioElement.play();
        
    } catch (error) {
        console.error('播放失败:', error);
    }
}
```

## 8. 服务器配置要求

### 8.1 环境依赖

- PHP 7.4+
- Laravel 8+
- Redis或Memcached（缓存支持）
- 足够的带宽和内存处理音频流

### 8.2 配置项

| 配置项 | 默认值 | 描述 |
|-------|--------|------|
| 音频数据缓存时间 | 30秒 | 缓存前30秒的音频数据 |
| 播放链接有效期 | 3600秒 | 1小时 |
| 缓冲区大小 | 1MB | 音频流处理缓冲区 |

## 9. 监控与日志

系统会记录以下关键日志：

- 播放链接生成请求
- 音频流代理请求
- 错误和异常信息
- 缓存命中情况

日志位置：`storage/logs/laravel.log`

## 10. 版本信息

- 当前版本：v1.0
- 发布日期：2024-01-19
- 支持的音频格式：MP3, HLS(m3u8)