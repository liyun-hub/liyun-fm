# Bootstrap 目录

该目录包含 Laravel 应用程序的启动文件。

## 概述

bootstrap 目录包含用于引导 Laravel 应用程序的文件，包括自动加载器配置和应用程序实例创建。

## 结构

```
bootstrap/
├── app.php            # 应用程序实例创建
├── autoload.php       # 自动加载器配置
└── cache/             # 缓存目录
```

## 核心文件

- **app.php**: 创建和配置 Laravel 应用程序实例
- **autoload.php**: 初始化 Composer 自动加载器
- **cache/**: 存储应用程序的缓存文件

## 用途

该目录负责在请求处理开始时引导 Laravel 应用程序，确保所有依赖和服务都正确初始化。