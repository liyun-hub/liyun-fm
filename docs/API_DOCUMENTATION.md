# FM Radio Player V0.1 - API接口文档

## 1. 文档概述

### 1.1 文档说明

本文档描述了FM Radio Player V0.1版本的API接口规范，包括接口URL、请求方法、参数说明、返回格式和错误码等信息。

### 1.2 接口基础信息

- 接口前缀：`/api`
- 基础URL：`http://fm.liy.ink/api`
- 响应格式：JSON
- 签名机制：HMAC-SHA256

### 1.3 通用响应格式

```json
{
  "code": 200,
  "message": "success",
  "data": {...}
}
```

### 1.4 错误码说明

| 错误码 | 描述 |
|-------|------|
| 200 | 请求成功 |
| 202 | 正在处理中，请稍后重试 |
| 400 | 无效的请求参数 |
| 401 | 无效的播放链接或链接已过期 |
| 403 | 频道不可用 |
| 404 | 请求的资源不存在 |
| 500 | 服务器内部错误 |
| 503 | 音频处理服务暂时不可用 |

## 2. 接口列表

| 接口URL | 请求方法 | 功能描述 |
|---------|---------|----------|
| `/play/{channelId}` | GET | 生成频道播放链接 |
| `/stream` | GET | 代理播放流（HLS播放列表和切片） |
| `/{segment}.ts` | GET | 处理TS分片文件请求 |

## 3. 详细接口说明

### 3.1 生成频道播放链接

#### 3.1.1 接口URL

`GET /api/play/{channelId}`

#### 3.1.2 功能描述

生成带签名的频道播放链接，支持HLS格式。

#### 3.1.3 参数说明

| 参数名 | 类型 | 位置 | 必须 | 描述 |
|-------|------|------|------|------|
| `channelId` | int | URL路径 | 是 | 频道ID |

#### 3.1.4 请求示例

```
GET /api/play/1
```

#### 3.1.5 返回示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "play_url": "http://fm.liy.ink/api/stream?channel_id=1&timestamp=1766627703&signature=361bf058617a49d33a57d0571e649ac86ce13a46334c054e93d0c3c5f0eb11e5&hls=1",
    "channel_id": 1,
    "channel_title": "示例频道",
    "expires_at": 1766631303,
    "proxy_enabled": true,
    "logo": "http://example.com/logo.png"
  }
}
```

#### 3.1.6 错误示例

```json
{
  "code": 500,
  "message": "播放失败: 内部服务器错误"
}
```

### 3.2 代理播放流

#### 3.2.1 接口URL

`GET /api/stream`

#### 3.2.2 功能描述

代理播放流，处理HLS播放列表和切片请求。

#### 3.2.3 参数说明

| 参数名 | 类型 | 位置 | 必须 | 描述 |
|-------|------|------|------|------|
| `channel_id` | int | Query | 是 | 频道ID |
| `timestamp` | int | Query | 是 | 请求时间戳 |
| `signature` | string | Query | 是 | 请求签名 |
| `hls` | bool | Query | 是 | 是否使用HLS格式 |
| `segment` | string | Query | 否 | TS切片文件名（仅用于切片请求） |

#### 3.2.4 请求示例

- 获取HLS播放列表：
  ```
  GET /api/stream?channel_id=1&timestamp=1766627703&signature=361bf058617a49d33a57d0571e649ac86ce13a46334c054e93d0c3c5f0eb11e5&hls=1
  ```

- 获取HLS切片：
  ```
  GET /api/stream?channel_id=1&timestamp=1766627703&signature=361bf058617a49d33a57d0571e649ac86ce13a46334c054e93d0c3c5f0eb11e5&hls=1&segment=segment-000001.ts
  ```

#### 3.2.5 返回示例

- 返回HLS播放列表（Content-Type: application/vnd.apple.mpegurl）：
  ```
  #EXTM3U
  #EXT-X-VERSION:3
  #EXT-X-TARGETDURATION:10
  #EXT-X-MEDIA-SEQUENCE:1
  #EXTINF:10.0,
  http://fm.liy.ink/api/stream?channel_id=1&timestamp=1766627703&signature=361bf058617a49d33a57d0571e649ac86ce13a46334c054e93d0c3c5f0eb11e5&hls=1&segment=segment-000001.ts
  #EXTINF:10.0,
  http://fm.liy.ink/api/stream?channel_id=1&timestamp=1766627703&signature=361bf058617a49d33a57d0571e649ac86ce13a46334c054e93d0c3c5f0eb11e5&hls=1&segment=segment-000002.ts
  ```

- 返回HLS切片（Content-Type: video/MP2T）：
  ```
  [二进制TS切片数据]
  ```

- 播放列表未就绪：
  ```json
  {
    "code": 202,
    "message": "正在加载音频流，请稍候重试",
    "retry_after": 0.2,
    "channel_id": 1
  }
  ```

### 3.3 处理TS分片文件请求

#### 3.3.1 接口URL

`GET /api/{segment}.ts`

#### 3.3.2 功能描述

处理TS分片文件请求，返回TS切片数据。

#### 3.3.3 参数说明

| 参数名 | 类型 | 位置 | 必须 | 描述 |
|-------|------|------|------|------|
| `segment` | string | URL路径 | 是 | TS切片文件名 |
| `channel_id` | int | Query | 是 | 频道ID |
| `timestamp` | int | Query | 是 | 请求时间戳 |
| `signature` | string | Query | 是 | 请求签名 |

#### 3.3.4 请求示例

```
GET /api/segment-000001.ts?channel_id=1&timestamp=1766627703&signature=361bf058617a49d33a57d0571e649ac86ce13a46334c054e93d0c3c5f0eb11e5
```

#### 3.3.5 返回示例

```
[二进制TS切片数据]
```

## 4. API签名机制

### 4.1 签名生成

1. 生成时间戳（timestamp）：当前Unix时间戳
2. 构建签名数据：`{channel_id}:{timestamp}`
3. 使用HMAC-SHA256算法，以`app.key`为密钥生成签名

### 4.2 签名验证

1. 验证签名格式是否正确
2. 验证时间戳是否在有效期内（默认1小时）
3. 重新生成签名并与请求中的签名进行比对
4. 使用`hash_equals`函数进行安全比较

### 4.3 签名生成示例（PHP）

```php
$channelId = 1;
$timestamp = time();
$signingKey = config('app.key');
$data = "{$channelId}:{$timestamp}";
$signature = hash_hmac('sha256', $data, $signingKey);
```

## 5. 音频处理服务接口

### 5.1 服务概述

音频处理服务是一个独立的Python FastAPI服务，负责音频流的转码和处理。

### 5.2 主要功能

- 启动/停止音频处理进程
- 获取进程状态
- 获取HLS播放列表
- 获取HLS切片

### 5.3 服务配置

| 配置项 | 值 |
|-------|------|
| 服务URL | `http://localhost:8001` |
| 服务端口 | `8001` |
| 超时时间 | `2秒` |

## 6. 接口调用流程

### 6.1 播放流程

1. 客户端请求播放频道：`GET /api/play/{channelId}`
2. 服务器生成带签名的播放链接
3. 客户端使用DPlayer加载播放链接
4. DPlayer请求HLS播放列表：`GET /api/stream?params`
5. 服务器代理请求到音频处理服务
6. 服务器返回HLS播放列表，修改其中的TS切片URL
7. DPlayer请求TS切片：`GET /api/stream?params&segment=xxx.ts`
8. 服务器代理请求到音频处理服务
9. 服务器返回TS切片数据
10. DPlayer播放音频

### 6.2 跨页面播放流程

1. 用户在页面A点击播放频道
2. 播放状态保存到JavaScript变量中
3. 用户切换到页面B
4. 使用jQuery-PJAX实现无刷新页面切换
5. 页面B检测到已有播放状态
6. 继续使用DPlayer播放当前频道
7. 播放状态保持不变

## 7. 最佳实践

### 7.1 客户端最佳实践

- 实现指数退避重试机制，处理播放列表未就绪的情况
- 使用DPlayer和HLS.js进行播放，支持多种浏览器
- 实现跨页面播放状态保持
- 处理网络异常情况
- 实现播放错误提示

### 7.2 服务端最佳实践

- 合理设置缓存时间，减少请求频率
- 实现异步处理，提高系统响应速度
- 实现错误日志记录，便于调试
- 实现进程监控，确保服务稳定运行
- 实现自动恢复机制，处理异常情况

## 8. 常见问题

### 8.1 播放链接失效

**问题**：播放链接在一段时间后失效

**解决方案**：
- 播放链接有效期为1小时，过期后需要重新请求
- 实现客户端自动刷新机制，定期重新请求播放链接

### 8.2 播放列表未就绪

**问题**：请求播放列表时返回202状态

**解决方案**：
- 实现客户端重试机制，按照返回的`retry_after`参数进行重试
- 重试间隔建议为0.2-0.5秒
- 最多重试5次

### 8.3 音频处理服务不可用

**问题**：返回503状态码

**解决方案**：
- 实现客户端错误提示
- 建议用户稍后重试
- 服务器端实现自动恢复机制

### 8.4 TS切片请求失败

**问题**：TS切片请求返回404或500状态码

**解决方案**：
- 检查音频处理服务是否正常运行
- 检查FFmpeg进程是否正常
- 检查原始音频流是否可用
- 实现客户端错误处理，跳过失败的切片

## 9. 版本历史

| 版本 | 日期 | 更新内容 |
|-----|------|----------|
| V0.1 | 2025-12-25 | 初始版本，包含基本播放功能 |
