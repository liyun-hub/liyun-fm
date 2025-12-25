# 音频服务

该目录包含 FM 收音机播放器的 Python 音频处理服务。

## 概述

音频服务负责处理 FM 收音机播放器的音频流、转码和处理请求。

## 结构

```
audio-service/
├── app/                # FastAPI 应用程序代码
│   ├── api/            # API 端点
│   ├── services/       # 音频处理服务
│   ├── utils/          # 实用函数
│   └── main.py         # 应用程序入口点
├── config/             # 配置文件
├── logs/               # 日志文件
├── requirements.txt    # Python 依赖
└── README.md           # 本文档
```

## 技术栈

- **Python 3.12+**
- **FastAPI**: 现代 API 框架
- **FFmpeg**: 音频转码和流媒体处理
- **uvicorn**: ASGI 服务器

## 核心功能

- 音频流转码（MP3、HLS）
- 流代理和缓存
- 音频元数据提取
- 实时音频处理

## 快速开始

```bash
# 安装依赖
pip install -r requirements.txt

# 启动服务
uvicorn app.main:app --host 0.0.0.0 --port 8001 --reload
```

## API 端点

- `GET /api/stream/{channel_id}`: 获取频道的音频流
- `GET /api/metadata/{channel_id}`: 获取频道元数据
- `POST /api/transcode`: 转码音频流
