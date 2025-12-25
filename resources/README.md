# Resources 目录

该目录包含 FM 收音机播放器应用程序的资源文件。

## 概述

resources 目录包含应用程序的视图、CSS、JavaScript、语言文件和其他资源文件。

## 结构

```
resources/
├── css/               # 原始 CSS 文件
├── js/                # 原始 JavaScript 文件
├── lang/              # 语言文件
├── sass/              # SASS/SCSS 文件（可选）
├── views/             # Blade 模板视图
└── ...                # 其他资源文件
```

## 核心组件

- **views/**: 包含应用程序的 Blade 模板视图
- **js/**: 包含应用程序的原始 JavaScript 文件
- **css/**: 包含应用程序的原始 CSS 文件
- **lang/**: 包含应用程序的多语言支持文件

## 用途

该目录包含应用程序的所有前端资源，这些资源会被编译并放置到 public 目录中供访问。