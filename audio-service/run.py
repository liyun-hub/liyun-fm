"""
音频处理服务启动脚本

启动重构后的音频处理服务。
"""

import logging
import sys
import signal
import atexit

from app import app
from app.config import config
from app.audio_service import get_audio_service, shutdown_service

logger = logging.getLogger(__name__)


def signal_handler(signum, frame):
    """信号处理器"""
    logger.info(f"Received signal {signum}, shutting down...")
    shutdown_service()
    sys.exit(0)


def setup_signal_handlers():
    """设置信号处理器"""
    signal.signal(signal.SIGINT, signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)


def main():
    """主函数"""
    try:
        # 设置信号处理器
        setup_signal_handlers()
        
        # 注册退出处理器
        atexit.register(shutdown_service)
        
        logger.info(f'Starting Audio Service on {config.HOST}:{config.PORT}')
        logger.info(f'Debug mode: {config.DEBUG}')
        logger.info(f'Log level: {config.LOG_LEVEL}')
        
        # 启动 Flask 应用
        app.run(
            host=config.HOST,
            port=config.PORT,
            debug=config.DEBUG,
            threaded=True,
            use_reloader=False  # 禁用重载器以避免重复初始化
        )
        
    except KeyboardInterrupt:
        logger.info("Received keyboard interrupt, shutting down...")
    except Exception as e:
        logger.error(f"Failed to start audio service: {str(e)}")
        sys.exit(1)
    finally:
        shutdown_service()


if __name__ == '__main__':
    main()
