"""
依赖注入容器

管理所有组件的生命周期和依赖关系。
"""

import logging
import threading
from typing import Optional, Dict, Any
from contextlib import contextmanager

from app.config import config
from app.concurrency_control import ConcurrencyControl
from app.process_manager import ProcessManager
from app.idle_process_monitor import IdleProcessMonitor
from app.resource_cleaner import ResourceCleaner
from app.error_handler import ErrorHandler

logger = logging.getLogger(__name__)


class ServiceContainer:
    """
    服务容器
    
    管理所有服务组件的创建、配置和生命周期。
    """
    
    def __init__(self):
        self._services: Dict[str, Any] = {}
        self._initialized = False
        self._lock = threading.RLock()
        self._shutdown_event = threading.Event()
        
        logger.info("ServiceContainer initialized")
    
    def initialize(self):
        """初始化所有服务组件"""
        with self._lock:
            if self._initialized:
                logger.warning("ServiceContainer is already initialized")
                return
            
            try:
                logger.info("Initializing service components...")
                
                # 1. 初始化并发控制
                self._services['concurrency_control'] = ConcurrencyControl(
                    lock_dir=config.LOCK_DIR,
                    lock_timeout=config.LOCK_TIMEOUT
                )
                logger.debug("ConcurrencyControl initialized")
                
                # 2. 初始化错误处理器
                self._services['error_handler'] = ErrorHandler(
                    hls_output_dir=config.HLS_OUTPUT_DIR,
                    min_free_space_mb=config.MIN_FREE_SPACE_MB
                )
                logger.debug("ErrorHandler initialized")
                
                # 3. 初始化进程管理器
                self._services['process_manager'] = ProcessManager(
                    concurrency_control=self._services['concurrency_control'],
                    error_handler=self._services['error_handler']
                )
                logger.debug("ProcessManager initialized")
                
                # 4. 初始化空闲进程监控器
                self._services['idle_monitor'] = IdleProcessMonitor(
                    process_manager=self._services['process_manager'],
                    idle_timeout=config.IDLE_TIMEOUT,
                    check_interval=config.IDLE_CHECK_INTERVAL
                )
                logger.debug("IdleProcessMonitor initialized")
                
                # 5. 初始化资源清理器
                self._services['resource_cleaner'] = ResourceCleaner(
                    hls_output_dir=config.HLS_OUTPUT_DIR,
                    cleanup_interval=config.CLEANUP_INTERVAL,
                    max_age=config.HLS_MAX_AGE
                )
                logger.debug("ResourceCleaner initialized")
                
                self._initialized = True
                logger.info("All service components initialized successfully")
                
            except Exception as e:
                logger.error(f"Failed to initialize service components: {str(e)}")
                self._cleanup_partial_initialization()
                raise
    
    def start(self):
        """启动所有服务"""
        with self._lock:
            if not self._initialized:
                raise RuntimeError("ServiceContainer must be initialized before starting")
            
            if not self._shutdown_event.is_set():
                logger.warning("Services are already started")
                return
            
            try:
                logger.info("Starting background services...")
                
                # 清除关闭事件
                self._shutdown_event.clear()
                
                # 启动后台服务
                self._services['idle_monitor'].start()
                self._services['resource_cleaner'].start()
                
                logger.info("All background services started successfully")
                
            except Exception as e:
                logger.error(f"Failed to start services: {str(e)}")
                self._shutdown_event.set()
                raise
    
    def stop(self):
        """停止所有服务"""
        with self._lock:
            if not self._initialized:
                logger.warning("ServiceContainer is not initialized")
                return
            
            if self._shutdown_event.is_set():
                logger.warning("Services are already stopped")
                return
            
            try:
                logger.info("Stopping all services...")
                
                # 设置关闭事件
                self._shutdown_event.set()
                
                # 停止所有活跃进程
                if 'process_manager' in self._services:
                    processes = self._services['process_manager'].list_processes()
                    for process_info in processes:
                        if process_info.status.value == "running":
                            logger.info(f"Stopping process for channel {process_info.channel_id}")
                            self._services['process_manager'].stop_process(process_info.channel_id)
                
                # 停止后台服务
                if 'idle_monitor' in self._services:
                    self._services['idle_monitor'].stop()
                
                if 'resource_cleaner' in self._services:
                    self._services['resource_cleaner'].stop()
                
                logger.info("All services stopped successfully")
                
            except Exception as e:
                logger.error(f"Error stopping services: {str(e)}")
    
    def shutdown(self):
        """关闭容器并清理所有资源"""
        with self._lock:
            if not self._initialized:
                return
            
            try:
                logger.info("Shutting down ServiceContainer...")
                
                # 停止服务
                self.stop()
                
                # 清理服务实例
                self._services.clear()
                self._initialized = False
                
                logger.info("ServiceContainer shutdown completed")
                
            except Exception as e:
                logger.error(f"Error during shutdown: {str(e)}")
    
    def _cleanup_partial_initialization(self):
        """清理部分初始化的组件"""
        try:
            # 停止已启动的服务
            for service_name, service in self._services.items():
                if hasattr(service, 'stop'):
                    try:
                        service.stop()
                    except Exception as e:
                        logger.error(f"Error stopping {service_name} during cleanup: {str(e)}")
            
            # 清理服务字典
            self._services.clear()
            
        except Exception as e:
            logger.error(f"Error during cleanup: {str(e)}")
    
    def get_service(self, service_name: str):
        """获取指定的服务实例"""
        with self._lock:
            if not self._initialized:
                raise RuntimeError("ServiceContainer is not initialized")
            
            if service_name not in self._services:
                raise ValueError(f"Service '{service_name}' not found")
            
            return self._services[service_name]
    
    def is_running(self) -> bool:
        """检查服务是否在运行"""
        return self._initialized and not self._shutdown_event.is_set()
    
    def get_status(self) -> Dict[str, Any]:
        """获取所有服务的状态"""
        with self._lock:
            if not self._initialized:
                return {
                    'initialized': False,
                    'running': False,
                    'services': {}
                }
            
            try:
                process_manager = self._services['process_manager']
                processes = process_manager.list_processes()
                active_processes = [p for p in processes if p.status.value == "running"]
                
                # 获取系统健康状态
                error_handler = self._services['error_handler']
                health_status = error_handler.check_system_health()
                error_stats = error_handler.get_error_statistics()
                
                return {
                    'initialized': self._initialized,
                    'running': self.is_running(),
                    'services': {
                        'process_manager': {
                            'total_processes': len(processes),
                            'active_processes': len(active_processes)
                        },
                        'idle_monitor': {
                            'running': self._services['idle_monitor'].is_running()
                        },
                        'resource_cleaner': {
                            'running': self._services['resource_cleaner'].is_running()
                        },
                        'concurrency_control': {
                            'active_locks': len(self._services['concurrency_control'].list_active_locks())
                        },
                        'error_handler': {
                            'total_errors': error_stats['total_errors'],
                            'recovery_rate': error_stats['recovery_rate']
                        }
                    },
                    'system_health': health_status
                }
                
            except Exception as e:
                logger.error(f"Error getting service status: {str(e)}")
                return {
                    'initialized': self._initialized,
                    'running': self.is_running(),
                    'error': str(e)
                }
    
    @contextmanager
    def service_context(self):
        """服务上下文管理器"""
        try:
            self.initialize()
            self.start()
            yield self
        finally:
            self.shutdown()


# 全局服务容器实例
_container: Optional[ServiceContainer] = None
_container_lock = threading.RLock()


def get_container() -> ServiceContainer:
    """获取全局服务容器实例"""
    global _container
    with _container_lock:
        if _container is None:
            _container = ServiceContainer()
        return _container


def initialize_container():
    """初始化并启动服务容器"""
    container = get_container()
    if not container.is_running():
        container.initialize()
        container.start()
    return container


def shutdown_container():
    """关闭服务容器"""
    global _container
    with _container_lock:
        if _container is not None:
            _container.shutdown()
            _container = None