# FM Radio Player V0.1

基于Web的网络广播播放平台，允许用户在线收听全球各地的广播电台。

## 项目简介

FM Radio Player是一个使用Laravel和FastAPI开发的网络广播播放平台，支持多种音频流格式，提供流畅的播放体验和优质的用户界面。

## 功能特点

- 📻 **频道列表**：展示全球各地的广播电台
- 🔍 **频道搜索**：支持按名称搜索频道
- 🗂️ **频道分类**：按类别浏览频道
- ▶️ **在线播放**：支持HLS和MP3格式
- 🔊 **音量控制**：实时调整播放音量
- ⏸️ **播放控制**：播放/暂停/停止功能
- 🔄 **跨页播放**：页面切换时保持播放状态
- 📱 **响应式设计**：适配桌面和移动设备
- 🔒 **安全播放**：带签名的临时播放链接

## 技术栈

### 前端
- HTML5 + CSS3 + JavaScript (ES6+)
- jQuery 3.7.1
- jQuery-PJAX 2.0.1
- DPlayer 1.27.1 (音频播放器)
- HLS.js 1.6.15 (HLS流支持)
- Tailwind CSS 3.4.19 (样式框架)

### 后端
- PHP 7.4+
- Laravel 8.x (Web框架)
- MySQL 5.7+ (数据库)
- Redis (缓存)
- Nginx (Web服务器)

### 音频处理服务
- Python 3.12+
- FastAPI (API框架)
- FFmpeg (音频转码和流媒体处理)
- uvicorn (ASGI服务器)

### 开发工具
- Git (版本控制)
- Laravel Mix 6.0.6 (前端资源构建)
- Webpack (模块打包)
- Composer (PHP依赖管理)
- npm (JavaScript依赖管理)

## 项目结构

```
├── app/                  # Laravel应用代码
├── audio-service/        # Python音频处理服务
├── bootstrap/            # Laravel启动文件
├── config/               # 配置文件
├── database/             # 数据库迁移和种子
├── public/               # 静态资源
├── resources/            # 视图、CSS和JavaScript
├── routes/               # 路由定义
├── storage/              # 存储目录
├── tests/                # 测试文件
├── vendor/               # Composer依赖
├── .env.example          # 环境变量示例
├── artisan               # Laravel命令行工具
├── composer.json         # Composer配置
├── package.json          # npm配置
├── webpack.mix.js        # Laravel Mix配置
└── README.md             # 项目说明文档
```

## 安装部署

### 环境要求

- PHP 7.4+
- Node.js 14+
- MySQL 5.7+
- Redis 5+
- FFmpeg 4+

### 安装步骤

1. **克隆代码**
   ```bash
   git clone <repository-url> fm-radio-player
   cd fm-radio-player
   ```

2. **安装依赖**
   ```bash
   # 安装PHP依赖
   composer install --no-dev
   
   # 安装Node.js依赖
   npm install
   ```

3. **配置环境变量**
   ```bash
   cp .env.example .env
   nano .env
   ```
   
   配置数据库、Redis和其他服务参数，关键环境变量包括：
   
   - `APP_KEY`: 应用加密密钥，由`php artisan key:generate`生成
   - `DB_*`: 数据库连接配置
   - `REDIS_*`: Redis缓存配置
   - `AUDIO_SERVICE_URL`: 音频处理服务URL
   - `ADMIN_DEFAULT_PASSWORD`: 管理员默认密码
   - `TEST_USER_PASSWORD`: 测试用户密码

4. **生成应用密钥**
   ```bash
   php artisan key:generate
   ```

5. **运行数据库迁移**
   ```bash
   php artisan migrate --force
   ```

6. **构建静态资源**
   ```bash
   npm run prod
   ```

7. **优化应用**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. **配置Nginx**
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /path/to/fm-radio-player/public;
       
       index index.php index.html index.htm;
       
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
   }
   ```

9. **启动音频处理服务**
   ```bash
   cd audio-service
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   uvicorn app.main:app --host 0.0.0.0 --port 8001 --reload
   ```

## 文档

- **Git管理**：[GIT_MANAGEMENT.md](docs/GIT_MANAGEMENT.md)
- **项目架构**：[PROJECT_ARCHITECTURE.md](docs/PROJECT_ARCHITECTURE.md)
- **API接口**：[API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md)
- **构建与部署**：[BUILD_DEPLOYMENT.md](docs/BUILD_DEPLOYMENT.md)
- **代码规范**：[CODE_STANDARDS.md](docs/CODE_STANDARDS.md)
- **清理报告**：[cleanup_report.md](docs/cleanup_report.md)

## 开发指南

### 开发流程

1. 从master分支创建功能分支
2. 开发新功能或修复bug
3. 编写测试用例
4. 提交代码并创建Pull Request
5. 代码审查通过后合并到master

### 代码规范

- 遵循PSR-12 PHP代码规范
- 遵循ES6+ JavaScript代码规范
- 遵循PEP 8 Python代码规范
- 提交信息使用英文，格式清晰

### 测试

```bash
# 运行PHP单元测试
php artisan test

# 运行前端测试
npm run test
```

## 贡献

欢迎提交Issue和Pull Request！

### 贡献指南

1. Fork本项目
2. 创建功能分支
3. 提交代码
4. 推送至分支
5. 创建Pull Request

## 许可证

[MIT License](LICENSE)

## 联系方式

如有问题或建议，请通过以下方式联系：

- 项目地址：https://github.com/your-username/fm-radio-player
- 问题反馈：https://github.com/your-username/fm-radio-player/issues

## 致谢

感谢所有为本项目做出贡献的开发者和用户！

---

**FM Radio Player V0.1** - 享受全球广播的乐趣！ 🎶