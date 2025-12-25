# FM Radio Player V0.1 - 代码规范与常见问题解决方案

## 1. 文档概述

本文档描述了FM Radio Player V0.1版本的代码规范和常见问题解决方案，旨在帮助开发团队遵循一致的代码风格，提高代码质量和可维护性，并快速解决开发和部署过程中遇到的问题。

## 2. 前端代码规范

### 2.1 HTML规范

1. **文档结构**
   - 使用HTML5文档类型声明：`<!DOCTYPE html>`
   - 使用语义化标签：`<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`
   - 确保文档结构清晰，层次分明

2. **代码风格**
   - 缩进使用4个空格
   - 标签名使用小写
   - 属性值使用双引号
   - 自闭合标签省略斜杠：`<img src="...">`
   - 避免内联样式和内联脚本

3. **性能优化**
   - 减少HTTP请求，合并CSS和JavaScript文件
   - 使用延迟加载：`<img loading="lazy">`
   - 压缩HTML代码
   - 合理使用缓存

### 2.2 CSS规范

1. **样式组织**
   - 使用Tailwind CSS作为主要样式框架
   - 避免使用复杂的CSS选择器
   - 遵循BEM命名规范（Block, Element, Modifier）
   - 优先使用组件化样式

2. **代码风格**
   - 缩进使用4个空格
   - 每个选择器占一行
   - 属性值使用双引号
   - 结束括号单独占一行
   - 避免使用`!important`

3. **性能优化**
   - 避免使用CSS表达式
   - 减少CSS规则数量
   - 优化CSS选择器顺序
   - 使用CSS变量管理颜色和尺寸

### 2.3 JavaScript规范

1. **语言特性**
   - 使用ES6+语法
   - 使用`const`和`let`代替`var`
   - 优先使用箭头函数
   - 避免使用`eval()`和`with()`

2. **代码风格**
   - 缩进使用4个空格
   - 每行代码长度不超过100个字符
   - 运算符前后添加空格
   - 函数和方法名使用驼峰命名法
   - 变量名使用驼峰命名法
   - 常量名使用全大写，下划线分隔

3. **最佳实践**
   - 实现防御性编程，避免访问未定义的属性
   - 使用模块化设计
   - 实现错误处理
   - 优化性能，避免内存泄漏
   - 使用事件委托处理动态内容

### 2.4 jQuery规范

1. **使用原则**
   - 优先使用原生JavaScript
   - 避免过度使用jQuery
   - 合理使用jQuery插件
   - 优化jQuery选择器

2. **代码风格**
   - 使用`$`作为jQuery对象的前缀
   - 链式调用时，每个方法单独占一行
   - 避免在循环中使用jQuery选择器
   - 合理使用`on()`方法绑定事件

3. **性能优化**
   - 缓存jQuery对象
   - 减少DOM操作
   - 使用`detach()`和`attach()`优化大量DOM操作
   - 避免使用`live()`和`delegate()`方法

## 3. 后端代码规范

### 3.1 PHP规范

1. **语言特性**
   - 使用PHP 7.4+语法
   - 严格类型声明：`declare(strict_types=1);`
   - 优先使用类型提示
   - 避免使用`@`错误抑制符

2. **代码风格**
   - 遵循PSR-12代码规范
   - 缩进使用4个空格
   - 每行代码长度不超过100个字符
   - 类名使用帕斯卡命名法（PascalCase）
   - 方法名和变量名使用驼峰命名法（camelCase）
   - 常量名使用全大写，下划线分隔

3. **最佳实践**
   - 实现依赖注入
   - 使用设计模式
   - 实现错误处理和异常捕获
   - 优化数据库查询
   - 实现缓存机制

### 3.2 Laravel规范

1. **目录结构**
   - 遵循Laravel默认目录结构
   - 合理组织控制器、模型、服务和中间件
   - 使用资源控制器
   - 实现API版本控制

2. **路由规范**
   - 使用命名路由
   - 合理分组路由
   - 使用RESTful API设计
   - 实现路由缓存

3. **数据库规范**
   - 使用迁移管理数据库结构
   - 使用Eloquent ORM
   - 实现模型关系
   - 合理使用访问器和修改器
   - 实现软删除

4. **性能优化**
   - 实现查询缓存
   - 使用懒加载和预加载
   - 优化Eloquent查询
   - 实现队列处理异步任务
   - 使用Redis缓存

## 4. Python代码规范

### 4.1 语言特性

1. **版本要求**
   - 使用Python 3.12+
   - 遵循PEP 8代码规范
   - 使用类型注解
   - 优先使用f-strings格式化字符串

2. **代码风格**
   - 缩进使用4个空格
   - 每行代码长度不超过100个字符
   - 函数和方法名使用蛇形命名法（snake_case）
   - 类名使用帕斯卡命名法（PascalCase）
   - 变量名使用蛇形命名法（snake_case）
   - 常量名使用全大写，下划线分隔

3. **最佳实践**
   - 实现异常处理
   - 使用上下文管理器处理资源
   - 实现模块化设计
   - 使用FastAPI框架最佳实践
   - 实现依赖注入

### 4.2 FastAPI规范

1. **路由设计**
   - 使用RESTful API设计
   - 合理分组路由
   - 使用路径参数和查询参数
   - 实现请求验证
   - 实现响应模型

2. **中间件**
   - 合理使用中间件
   - 实现日志记录
   - 实现CORS处理
   - 实现认证和授权

3. **性能优化**
   - 实现异步处理
   - 使用连接池
   - 实现缓存机制
   - 优化数据库查询

## 5. 数据库规范

### 5.1 命名规范

1. **数据库命名**
   - 数据库名使用小写，下划线分隔：`fm_radio`
   - 表名使用复数形式：`channels`, `categories`
   - 字段名使用小写，下划线分隔：`channel_id`, `created_at`
   - 索引名使用`idx_`前缀：`idx_channel_id`
   - 外键名使用`fk_`前缀：`fk_channel_category_id`

2. **数据类型**
   - 优先使用合适的数据类型
   - 使用`VARCHAR`代替`TEXT`存储短文本
   - 使用`DATETIME`存储日期和时间
   - 使用`BIGINT`存储大数值
   - 使用`BOOLEAN`存储布尔值

### 5.2 设计原则

1. **范式设计**
   - 遵循第三范式
   - 合理设计表关系
   - 避免数据冗余

2. **索引优化**
   - 为经常查询的字段添加索引
   - 避免过度索引
   - 定期优化索引

3. **性能优化**
   - 分区表处理大数据
   - 合理使用视图
   - 实现读写分离
   - 优化查询语句

## 6. 代码审查规范

### 6.1 审查流程

1. **提交前检查**
   - 运行代码格式化工具
   - 运行单元测试
   - 运行静态代码分析
   - 检查代码风格

2. **审查要点**
   - 代码正确性
   - 代码可读性
   - 代码性能
   - 安全性
   - 可维护性
   - 测试覆盖率

3. **审查工具**
   - PHP: PHPStan, Psalm
   - JavaScript: ESLint, Prettier
   - Python: flake8, black, mypy
   - 数据库: EXPLAIN分析

## 7. 常见问题解决方案

### 7.1 播放相关问题

#### 7.1.1 播放失败："播放出错，请重试"

**问题描述**：用户点击播放按钮后，出现"播放出错，请重试"错误。

**解决方案**：

1. **检查音频处理服务状态**
   ```bash
   systemctl status audio-service
   ```

2. **检查FFmpeg进程**
   ```bash
   ps aux | grep ffmpeg
   ```

3. **检查原始音频流**
   ```bash
   curl -I <stream-url>
   ```

4. **检查音频服务日志**
   ```bash
   tail -f /www/wwwroot/fm.liy.ink/audio-service/logs/app.log
   ```

5. **检查应用日志**
   ```bash
   tail -f /www/wwwroot/fm.liy.ink/storage/logs/laravel.log
   ```

#### 7.1.2 播放链接失效

**问题描述**：播放链接在一段时间后失效。

**解决方案**：

1. **检查签名有效期**
   - 默认有效期为1小时，可在`PlayController.php`中修改`$urlTtl`变量
   - 实现客户端自动刷新机制

2. **检查时间戳同步**
   - 确保服务器时间准确
   - 实现时间戳容错机制

#### 7.1.3 跨页面播放停止

**问题描述**：用户切换页面后，播放停止。

**解决方案**：

1. **检查PJAX配置**
   - 确保PJAX目标元素正确
   - 检查PJAX事件处理

2. **检查播放器状态管理**
   - 确保播放器状态在页面切换时被保存
   - 检查`app.js`中的PJAX事件处理

### 7.2 部署相关问题

#### 7.2.1 500 Internal Server Error

**问题描述**：访问网站时出现500错误。

**解决方案**：

1. **检查环境变量配置**
   ```bash
   cat .env
   ```

2. **检查文件权限**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www:www storage bootstrap/cache
   ```

3. **检查PHP扩展**
   ```bash
   php -m | grep -E "mbstring|pdo_mysql|redis"
   ```

4. **检查日志**
   ```bash
   tail -f /var/log/nginx/fm.liy.ink.error.log
   tail -f /www/wwwroot/fm.liy.ink/storage/logs/laravel.log
   ```

#### 7.2.2 404 Not Found

**问题描述**：访问页面时出现404错误。

**解决方案**：

1. **检查Nginx配置**
   ```bash
   nginx -t
   systemctl restart nginx
   ```

2. **检查路由**
   ```bash
   php artisan route:list
   php artisan route:cache
   ```

3. **检查文件存在性**
   ```bash
   ls -la /www/wwwroot/fm.liy.ink/public
   ```

#### 7.2.3 缓存问题

**问题描述**：修改代码后，网站没有更新。

**解决方案**：

1. **清除Laravel缓存**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

2. **清除浏览器缓存**
   - 按`Ctrl+F5`强制刷新
   - 或清除浏览器缓存

3. **检查CDN缓存**
   - 手动刷新CDN缓存
   - 或等待缓存过期

### 7.3 性能问题

#### 7.3.1 页面加载缓慢

**问题描述**：网站页面加载时间长。

**解决方案**：

1. **优化静态资源**
   - 压缩CSS和JavaScript文件
   - 使用CDN加速静态资源
   - 实现资源版本控制

2. **优化数据库查询**
   - 添加索引
   - 优化查询语句
   - 使用缓存

3. **优化代码**
   - 减少HTTP请求
   - 实现懒加载
   - 优化图片大小

#### 7.3.2 高并发访问问题

**问题描述**：网站在高并发访问时性能下降。

**解决方案**：

1. **使用缓存**
   - Redis缓存
   - 页面缓存
   - 查询缓存

2. **优化数据库**
   - 实现读写分离
   - 优化数据库配置
   - 使用连接池

3. **使用负载均衡**
   - 配置Nginx负载均衡
   - 实现水平扩展

### 7.4 错误处理与调试

#### 7.4.1 启用调试模式

**问题描述**：需要查看详细的错误信息。

**解决方案**：

1. **修改环境变量**
   ```bash
   # .env文件
   APP_DEBUG=true
   ```

2. **重启应用**
   ```bash
   php artisan config:cache
   ```

#### 7.4.2 日志分析

**问题描述**：需要分析应用日志。

**解决方案**：

1. **使用日志分析工具**
   - ELK Stack
   - Graylog
   - 或简单的grep命令

2. **常见日志分析命令**
   ```bash
   # 查找错误日志
   grep -i "error" /www/wwwroot/fm.liy.ink/storage/logs/laravel.log
   
   # 查找特定频道的日志
   grep -i "channel_id: 1" /www/wwwroot/fm.liy.ink/storage/logs/laravel.log
   
   # 实时查看日志
   tail -f /www/wwwroot/fm.liy.ink/storage/logs/laravel.log
   ```

### 7.5 开发环境问题

#### 7.5.1 依赖安装失败

**问题描述**：安装依赖时出现错误。

**解决方案**：

1. **PHP依赖安装失败**
   ```bash
   # 清除Composer缓存
   composer clear-cache
   
   # 重新安装依赖
   composer install --no-dev
   ```

2. **Node.js依赖安装失败**
   ```bash
   # 清除npm缓存
   npm cache clean --force
   
   # 重新安装依赖
   rm -rf node_modules package-lock.json
   npm install
   ```

#### 7.5.2 构建失败

**问题描述**：运行`npm run prod`时构建失败。

**解决方案**：

1. **检查Node.js版本**
   ```bash
   node -v
   # 确保Node.js版本 >= 14
   ```

2. **检查依赖版本**
   ```bash
   npm outdated
   ```

3. **查看详细错误信息**
   ```bash
   npm run prod --verbose
   ```

## 8. 调试技巧

### 8.1 前端调试

1. **浏览器开发者工具**
   - 检查控制台错误
   - 网络请求分析
   - 元素检查
   - 性能分析

2. **DPlayer调试**
   ```javascript
   // 启用DPlayer调试模式
   const dp = new DPlayer({
     // ...
     logLevel: 'debug'
   });
   ```

3. **HLS.js调试**
   ```javascript
   // 启用HLS.js调试模式
   Hls.DefaultConfig.logger.level = Hls.LogLevel.DEBUG;
   ```

### 8.2 后端调试

1. **使用Laravel Debugbar**
   - 安装：`composer require barryvdh/laravel-debugbar --dev`
   - 配置：`APP_DEBUG=true`
   - 访问页面时查看调试信息

2. **使用Xdebug**
   - 安装Xdebug扩展
   - 配置PHPStorm或VS Code
   - 设置断点调试

3. **日志调试**
   ```php
   // 在代码中添加日志
   Log::info('调试信息', ['key' => 'value']);
   ```

### 8.3 Python服务调试

1. **使用FastAPI自动文档**
   - 访问：`http://localhost:8001/docs`
   - 测试API接口

2. **使用uvicorn调试模式**
   ```bash
   uvicorn app.main:app --reload --debug
   ```

3. **日志调试**
   ```python
   # 在代码中添加日志
   logger.info("调试信息", extra={"key": "value"})
   ```

## 9. 安全最佳实践

### 9.1 前端安全

1. **XSS防护**
   - 使用HTML转义
   - 避免使用`innerHTML`
   - 使用CSP（内容安全策略）

2. **CSRF防护**
   - 使用Laravel内置的CSRF保护
   - 验证AJAX请求的CSRF令牌

3. **点击劫持防护**
   - 使用X-Frame-Options头
   - 使用Content-Security-Policy头

### 9.2 后端安全

1. **SQL注入防护**
   - 使用Eloquent ORM
   - 使用参数化查询
   - 避免直接拼接SQL语句

2. **认证与授权**
   - 使用Laravel内置的认证系统
   - 实现RBAC（基于角色的访问控制）
   - 使用API签名机制

3. **输入验证**
   - 使用Laravel验证器
   - 验证所有用户输入
   - 实现服务器端验证

4. **安全头**
   - 使用HTTPS
   - 配置安全响应头
   - 使用HSTS（HTTP严格传输安全）

## 10. 结论

本文档提供了FM Radio Player V0.1版本的代码规范和常见问题解决方案，旨在帮助开发团队遵循一致的代码风格，提高代码质量和可维护性，并快速解决开发和部署过程中遇到的问题。

开发团队应严格遵守本文档中的代码规范，并在遇到问题时参考常见问题解决方案。定期更新本文档，以适应项目的发展和变化。