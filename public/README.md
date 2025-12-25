# Public 目录

该目录包含 FM 收音机播放器应用程序的所有公共资源。

## 概述

public 目录是应用程序的网站根目录，包含所有可通过 HTTP 请求直接访问的文件。

## 结构

```
public/
├── css/               # 编译后的 CSS 文件
├── js/                # 编译后的 JavaScript 文件
├── fonts/             # 字体文件
├── images/            # 图像资源
├── sounds/            # 音效文件
├── index.php          # 应用程序入口点
└── robots.txt         # 搜索引擎爬虫规则
```

## 核心组件

- **index.php**: 所有 HTTP 请求的主要入口点
- **css/**: 包含编译后的 Tailwind CSS 和自定义样式
- **js/**: 包含编译后的 JavaScript，以及 jQuery、DPlayer 和 HLS.js 等依赖
- **images/**: 应用程序的 logo、图标和其他视觉资源

## 用途

所有面向公众的资源都应放置在此目录中。Laravel 应用程序将此目录用作网站根目录。