# FM Radio Player V0.1 - 构建与部署流程

## 1. 构建环境设置

### 1.1 系统要求

| 环境 | 版本 |
|------|------|
| PHP | 7.4+ |
| Node.js | 14+ |
| npm | 6+ |
| Composer | 2+ |
| MySQL | 5.7+ |
| Redis | 5+ |
| FFmpeg | 4+ |

### 1.2 依赖安装

#### 1.2.1 PHP依赖

```bash
cd /www/wwwroot/fm.liy.ink
composer install --no-dev
```

#### 1.2.2 Node.js依赖

```bash
cd /www/wwwroot/fm.liy.ink
npm install
```

## 2. 构建过程

### 2.1 开发环境构建

```bash
# 开发模式构建
npm run dev

# 监听文件变化，自动重新构建
npm run watch
```

### 2.2 生产环境构建

```bash
# 生产模式构建（压缩优化）
npm run prod
```

### 2.3 构建输出

构建完成后，生成的文件将输出到`public`目录：

| 文件路径 | 用途 |
|---------|------|
| `public/js/app.js` | 主JavaScript文件 |
| `public/js/DPlayer.min.js` | DPlayer播放器库 |
| `public/js/hls.min.js` | HLS.js库 |
| `public/css/app.css` | 主CSS文件 |
| `public/mix-manifest.json` | 资源版本映射 |

## 3. 部署架构

### 3.1 系统架构

```
┌───────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                             客户端层                                             │
├───────────────────────────────────────────────────────────────────────────────────────────────────┤
│  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐       │
│  │   桌面浏览器      │  │   移动浏览器      │  │   其他客户端      │  │      CDN缓存      │       │
│  └───────────────────┘  └───────────────────┘  └───────────────────┘  └───────────────────┘       │
└───────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                    │
                                                    ▼
┌───────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                             负载均衡层                                             │
├───────────────────────────────────────────────────────────────────────────────────────────────────┤
│  ┌───────────────────┐                                                                           │
│  │       Nginx       │                                                                           │
│  └───────────────────┘                                                                           │
└───────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                    │
                                                    ▼
┌───────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                             应用层                                                 │
├───────────────────────────────────────────────────────────────────────────────────────────────────┤
│  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐       │
│  │    Nginx Web      │  │    Laravel App    │  │     Redis缓存     │  │     MySQL数据库    │       │
│  │     服务器        │  │                   │  │                   │  │                   │       │
│  └───────────────────┘  └───────────────────┘  └───────────────────┘  └───────────────────┘       │
└───────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                    │
                                                    ▼
┌───────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                             服务层                                                 │
├───────────────────────────────────────────────────────────────────────────────────────────────────┤
│  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐       │
│  │   Python服务      │  │    FFmpeg进程     │  │    进程监控       │  │    日志服务       │       │
│  │   (FastAPI)       │  │                   │  │                   │  │                   │       │
│  └───────────────────┘  └───────────────────┘  └───────────────────┘  └───────────────────┘       │
└───────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### 3.2 服务配置

| 服务 | 配置项 | 值 |
|------|-------|------|
| Nginx | 监听端口 | 80, 443 |
| Laravel | APP_ENV | production |
| Laravel | APP_DEBUG | false |
| Python服务 | 监听端口 | 8001 |
| Redis | 监听端口 | 6379 |
| MySQL | 监听端口 | 3306 |

## 4. 部署步骤

### 4.1 准备工作

1. **克隆代码仓库**
   ```bash
   git clone <repository-url> /www/wwwroot/fm.liy.ink
   cd /www/wwwroot/fm.liy.ink
   git checkout master
   ```

2. **配置环境变量**
   ```bash
   cp .env.example .env
   nano .env
   ```
   
   配置以下关键项：
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://fm.liy.ink
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=fm_radio
   DB_USERNAME=root
   DB_PASSWORD=password
   
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   
   AUDIO_SERVICE_URL=http://localhost:8001
   ```

3. **生成应用密钥**
   ```bash
   php artisan key:generate
   ```

### 4.2 数据库部署

1. **创建数据库**
   ```bash
   mysql -u root -p
   CREATE DATABASE fm_radio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit;
   ```

2. **运行数据库迁移**
   ```bash
   php artisan migrate --force
   ```

3. **导入初始数据**
   ```bash
   # 如果有初始数据SQL文件
   mysql -u root -p fm_radio < initial_data.sql
   ```

### 4.3 应用部署

1. **安装依赖**
   ```bash
   # 安装PHP依赖
   composer install --no-dev --optimize-autoloader
   
   # 安装Node.js依赖
   npm install --production
   ```

2. **构建静态资源**
   ```bash
   npm run prod
   ```

3. **优化应用**
   ```bash
   # 缓存配置
   php artisan config:cache
   
   # 缓存路由
   php artisan route:cache
   
   # 缓存视图
   php artisan view:cache
   
   # 清理缓存
   php artisan cache:clear
   ```

4. **设置文件权限**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www:www storage bootstrap/cache
   ```

### 4.4 Nginx配置

创建Nginx配置文件：`/etc/nginx/conf.d/fm.liy.ink.conf`

```nginx
server {
    listen 80;
    server_name fm.liy.ink;
    root /www/wwwroot/fm.liy.ink/public;
    
    index index.php index.html index.htm;
    
    # 静态资源缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # HLS流缓存配置
    location ~* \.m3u8$ {
        expires 1s;
        add_header Cache-Control "no-cache";
    }
    
    location ~* \.ts$ {
        expires 60s;
        add_header Cache-Control "public";
    }
    
    # PHP处理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        try_files $uri /index.php =404;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
    }
    
    # 错误日志
    error_log /var/log/nginx/fm.liy.ink.error.log;
    access_log /var/log/nginx/fm.liy.ink.access.log;
}
```

重启Nginx服务：
```bash
systemctl restart nginx
```

### 4.5 音频处理服务部署

1. **进入音频服务目录**
   ```bash
   cd /www/wwwroot/fm.liy.ink/audio-service
   ```

2. **创建虚拟环境**
   ```bash
   python3 -m venv venv
   source venv/bin/activate
   ```

3. **安装Python依赖**
   ```bash
   pip install -r requirements.txt
   ```

4. **配置服务**
   ```bash
   cp .env.example .env
   nano .env
   ```

5. **启动服务**
   ```bash
   # 使用systemd服务
   cp audio-service.service /etc/systemd/system/
   systemctl enable audio-service
   systemctl start audio-service
   ```

## 5. 部署后检查

### 5.1 应用状态检查

1. **检查应用是否正常运行**
   ```bash
   curl -I http://fm.liy.ink
   ```
   
   预期返回：`HTTP/1.1 200 OK`

2. **检查API是否正常响应**
   ```bash
   curl http://fm.liy.ink/api/play/1
   ```
   
   预期返回：JSON格式的播放链接

3. **检查音频处理服务状态**
   ```bash
   systemctl status audio-service
   ```
   
   预期返回：`active (running)`

### 5.2 日志检查

1. **检查应用日志**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **检查Nginx日志**
   ```bash
   tail -f /var/log/nginx/fm.liy.ink.error.log
   ```

3. **检查音频服务日志**
   ```bash
   tail -f /var/log/audio-service.log
   ```

## 6. 自动部署

### 6.1 GitHub Actions配置

创建`.github/workflows/deploy.yml`文件：

```yaml
name: Deploy to Production

on:
  push:
    branches:
      - master

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v2
      
      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: mbstring, pdo_mysql, redis
      
      - name: Install dependencies
        run: |
          composer install --no-dev --optimize-autoloader
          npm install --production
      
      - name: Build assets
        run: npm run prod
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USERNAME }}
          password: ${{ secrets.SERVER_PASSWORD }}
          port: ${{ secrets.SERVER_PORT }}
          script: |
            cd /www/wwwroot/fm.liy.ink
            git pull origin master
            composer install --no-dev --optimize-autoloader
            npm install --production
            npm run prod
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan cache:clear
            chmod -R 775 storage bootstrap/cache
            chown -R www:www storage bootstrap/cache
            systemctl restart audio-service
```

## 7. 故障排除

### 7.1 常见问题

1. **500 Internal Server Error**
   - 检查`.env`文件配置是否正确
   - 检查`storage/logs/laravel.log`日志
   - 确保文件权限设置正确
   - 检查PHP扩展是否安装完整

2. **404 Not Found**
   - 检查Nginx配置是否正确
   - 确保`public`目录权限正确
   - 检查路由是否存在
   - 运行`php artisan route:cache`刷新路由缓存

3. **播放失败**
   - 检查音频处理服务是否正常运行
   - 检查FFmpeg进程是否存在
   - 检查原始音频流是否可用
   - 检查`audio-service/logs`目录下的日志

4. **缓存问题**
   - 运行`php artisan cache:clear`清除缓存
   - 运行`php artisan config:clear`清除配置缓存
   - 运行`php artisan route:clear`清除路由缓存
   - 运行`php artisan view:clear`清除视图缓存

5. **权限问题**
   ```bash
   # 重置文件权限
   chmod -R 775 storage bootstrap/cache
   chown -R www:www storage bootstrap/cache
   ```

### 7.2 监控与告警

1. **设置监控**
   - 使用Prometheus监控服务器状态
   - 使用Grafana创建监控仪表盘
   - 监控Nginx访问日志和错误日志
   - 监控Laravel应用日志

2. **设置告警**
   - 配置服务器负载告警
   - 配置应用错误率告警
   - 配置数据库连接数告警
   - 配置音频服务状态告警

## 8. 版本管理

### 8.1 资源版本控制

Laravel Mix自动生成版本号，确保静态资源在部署后能正确更新：

- 生成带有哈希值的文件名：`app.js?id=1234567890`
- 生成`mix-manifest.json`文件记录版本映射
- 浏览器自动加载新版本资源

### 8.2 CDN缓存刷新

如果使用CDN，部署后需要刷新CDN缓存：

1. 手动刷新CDN缓存控制面板
2. 使用CDN API自动刷新
3. 配置CDN缓存规则，优先使用文件名哈希

## 9. 回滚策略

### 9.1 代码回滚

```bash
# 回滚到上一个版本
git checkout HEAD~1
composer install --no-dev --optimize-autoloader
npm run prod
php artisan migrate:rollback --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
```

### 9.2 数据库回滚

```bash
# 回滚最近一次迁移
php artisan migrate:rollback

# 回滚到指定迁移
php artisan migrate:rollback --step=3
```

## 10. 结论

FM Radio Player V0.1的构建与部署流程涵盖了从代码拉取、依赖安装、构建、部署到监控的完整生命周期。通过合理的部署架构和自动化部署方案，可以确保应用的高可用性和稳定性。

建议定期进行部署演练，确保团队成员熟悉部署流程，并建立完善的监控和告警机制，及时发现和解决问题。