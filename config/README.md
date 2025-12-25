# Config 目录

该目录包含 FM 收音机播放器应用程序的所有配置文件。

## 概述

config 目录包含应用程序的所有配置文件，用于控制应用程序的行为和设置。

## 结构

```
config/
├── app.php            # 应用程序基本配置
├── auth.php           # 认证配置
├── broadcasting.php   # 广播配置
├── cache.php          # 缓存配置
├── database.php       # 数据库配置
├── filesystems.php    # 文件系统配置
├── logging.php        # 日志配置
├── mail.php           # 邮件配置
├── queue.php          # 队列配置
├── services.php       # 外部服务配置
├── session.php        # 会话配置
├── view.php           # 视图配置
└── ...                # 其他配置文件
```

## 核心配置

- **app.php**: 设置应用程序名称、环境、时区等基本信息
- **database.php**: 配置数据库连接和设置
- **cache.php**: 配置缓存驱动和选项
- **logging.php**: 配置日志记录方式和级别
- **services.php**: 配置外部服务（如 Redis、AWS 等）的凭据

## 用途

该目录包含应用程序的所有配置，通过修改这些文件可以自定义应用程序的行为，而无需修改核心代码。