# App 目录

该目录包含 Laravel 框架的核心应用程序代码。

## 结构

```
app/
├── Console/           # Artisan 命令行工具
├── Exceptions/        # 异常处理
├── Http/              # 控制器和中间件
│   ├── Controllers/   # 应用程序控制器
│   ├── Middleware/    # HTTP 中间件
│   └── Requests/      # 表单请求验证
├── Models/            # Eloquent 模型
├── Providers/         # 服务提供者
└── Services/          # 业务逻辑服务
```

## 核心组件

- **Controllers**: 处理 HTTP 请求和响应
- **Models**: 表示数据库表和关系
- **Middleware**: 在请求到达控制器之前进行处理
- **Services**: 封装业务逻辑
- **Exceptions**: 定义自定义异常处理程序

## 用途

该目录遵循 Laravel 的 MVC（模型-视图-控制器）模式，包含应用程序的所有核心功能。
