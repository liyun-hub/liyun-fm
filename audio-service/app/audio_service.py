"""
音频处理服务主应用

集成所有重构的组件，提供统一的服务接口。
"""

import logging
from typing import Optional

from app.container import get_container, initialize_container, shutdown_container

logger = logging.getLogger(__name__)


class AudioService:
    """
    音频处理服务主类
    
    使用服务容器管理所有组件。
    """
    
    def __init__(self):
        self.container = get_container()
        logger.info("AudioService initialized with ServiceContainer")
    
    def start(self):
        """启动服务"""
        try:
            self.container.initialize()
            self.container.start()
            logger.info("AudioService started successfully")
        except Exception as e:
            logger.error(f"Failed to start AudioService: {str(e)}")
            raise
    
    def stop(self):
        """停止服务"""
        try:
            self.container.stop()
            logger.info("AudioService stopped successfully")
        except Exception as e:
            logger.error(f"Error stopping AudioService: {str(e)}")
    
    def shutdown(self):
        """关闭服务"""
        try:
            self.container.shutdown()
            logger.info("AudioService shutdown completed")
        except Exception as e:
            logger.error(f"Error during AudioService shutdown: {str(e)}")
    
    def is_running(self) -> bool:
        """检查服务是否在运行"""
        return self.container.is_running()
    
    def get_status(self) -> dict:
        """获取服务状态"""
        return self.container.get_status()
    
    # 便捷方法，用于访问各个组件
    @property
    def process_manager(self):
        """获取进程管理器"""
        return self.container.get_service('process_manager')
    
    @property
    def error_handler(self):
        """获取错误处理器"""
        return self.container.get_service('error_handler')
    
    @property
    def concurrency_control(self):
        """获取并发控制器"""
        return self.container.get_service('concurrency_control')
    
    @property
    def idle_monitor(self):
        """获取空闲进程监控器"""
        return self.container.get_service('idle_monitor')
    
    @property
    def resource_cleaner(self):
        """获取资源清理器"""
        return self.container.get_service('resource_cleaner')


# 全局服务实例
audio_service: Optional[AudioService] = None


def get_audio_service() -> AudioService:
    """获取全局音频服务实例"""
    global audio_service
    if audio_service is None:
        audio_service = AudioService()
    return audio_service


def initialize_service():
    """初始化并启动服务"""
    service = get_audio_service()
    if not service.is_running():
        service.start()
    return service


def shutdown_service():
    """关闭服务"""
    global audio_service
    if audio_service is not None:
        audio_service.shutdown()
        audio_service = None