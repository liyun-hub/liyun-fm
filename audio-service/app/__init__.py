"""
Flask 应用初始化

创建 Flask 应用实例并配置日志。
"""

import logging
import sys
import atexit
from flask import Flask

from app.config import config

# 配置日志
def setup_logging():
    """设置日志配置"""
    log_level = getattr(logging, config.LOG_LEVEL.upper(), logging.INFO)
    
    logging.basicConfig(
        level=log_level,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler('audio_service.log'),
            logging.StreamHandler(sys.stdout)
        ]
    )
    
    # 设置第三方库的日志级别
    logging.getLogger('werkzeug').setLevel(logging.WARNING)
    logging.getLogger('urllib3').setLevel(logging.WARNING)

# 设置日志
setup_logging()

# 创建 Flask 应用
app = Flask(__name__)
app.config['DEBUG'] = config.DEBUG

# 注册应用关闭处理器
def shutdown_handler():
    """应用关闭处理器"""
    try:
        from app.audio_service import shutdown_service
        shutdown_service()
        logger.info("Services shutdown completed")
    except Exception as e:
        logger.error(f"Error during service shutdown: {str(e)}")

atexit.register(shutdown_handler)

# 导入路由（在应用创建后导入以避免循环导入）
from app import routes

logger = logging.getLogger(__name__)
logger.info("Flask application initialized")

# 延迟初始化服务（避免循环导入）
def lazy_initialize_services():
    """延迟初始化服务"""
    try:
        from app.audio_service import initialize_service
        initialize_service()
        logger.info("Services initialized")
        return True
    except Exception as e:
        logger.error(f"Failed to initialize services: {str(e)}")
        return False

# 标记服务是否已初始化
_services_initialized = False

def ensure_services_initialized():
    """确保服务已初始化"""
    global _services_initialized
    if not _services_initialized:
        _services_initialized = lazy_initialize_services()
    return _services_initialized