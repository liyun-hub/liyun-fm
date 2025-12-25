"""
空闲进程监控器

监控进程活动时间，自动停止长时间无活动的进程。
"""

import threading
import time
import logging
from datetime import datetime, timezone
from typing import TYPE_CHECKING

if TYPE_CHECKING:
    from app.process_manager import ProcessManager

logger = logging.getLogger(__name__)


class IdleProcessMonitor:
    """
    空闲进程监控器
    
    定期检查进程的最后活动时间，自动停止超过空闲时间的进程。
    """
    
    def __init__(self, process_manager: 'ProcessManager', idle_timeout: int = 300, check_interval: int = 60):
        self.process_manager = process_manager
        self.idle_timeout = idle_timeout  # 空闲超时时间（秒）
        self.check_interval = check_interval  # 检查间隔（秒）
        
        self._running = False
        self._thread: threading.Thread = None
        self._stop_event = threading.Event()
        
        logger.info(f"IdleProcessMonitor initialized with idle_timeout={idle_timeout}s, check_interval={check_interval}s")
    
    def start(self):
        """启动监控器"""
        if self._running:
            logger.warning("IdleProcessMonitor is already running")
            return
        
        self._running = True
        self._stop_event.clear()
        
        self._thread = threading.Thread(
            target=self._monitor_loop,
            name="IdleProcessMonitor",
            daemon=True
        )
        self._thread.start()
        
        logger.info("IdleProcessMonitor started")
    
    def stop(self):
        """停止监控器"""
        if not self._running:
            logger.warning("IdleProcessMonitor is not running")
            return
        
        logger.info("Stopping IdleProcessMonitor...")
        
        self._running = False
        self._stop_event.set()
        
        if self._thread and self._thread.is_alive():
            self._thread.join(timeout=5)
            if self._thread.is_alive():
                logger.warning("IdleProcessMonitor thread did not stop gracefully")
        
        logger.info("IdleProcessMonitor stopped")
    
    def is_running(self) -> bool:
        """检查监控器是否在运行"""
        return self._running and self._thread and self._thread.is_alive()
    
    def _monitor_loop(self):
        """监控循环"""
        logger.info("IdleProcessMonitor loop started")
        
        while self._running and not self._stop_event.is_set():
            try:
                self._check_idle_processes()
            except Exception as e:
                logger.error(f"Error in idle process monitoring: {str(e)}")
            
            # 等待下一次检查
            if self._stop_event.wait(timeout=self.check_interval):
                break
        
        logger.info("IdleProcessMonitor loop stopped")
    
    def _check_idle_processes(self):
        """检查空闲进程"""
        current_time = datetime.now(timezone.utc)
        stopped_count = 0
        
        try:
            processes = self.process_manager.list_processes()
            running_processes = [p for p in processes if p.status.value == "running"]
            
            if not running_processes:
                logger.debug("No running processes to check")
                return
            
            logger.debug(f"Checking {len(running_processes)} running processes for idle timeout")
            
            for process_info in running_processes:
                try:
                    # 计算空闲时间
                    idle_seconds = (current_time - process_info.last_activity_time).total_seconds()
                    
                    if idle_seconds > self.idle_timeout:
                        logger.info(
                            f"Process for channel {process_info.channel_id} has been idle for "
                            f"{idle_seconds:.0f}s (threshold: {self.idle_timeout}s), stopping..."
                        )
                        
                        # 停止空闲进程
                        success = self.process_manager.stop_process(process_info.channel_id)
                        if success:
                            stopped_count += 1
                            logger.info(f"Successfully stopped idle process for channel {process_info.channel_id}")
                        else:
                            logger.warning(f"Failed to stop idle process for channel {process_info.channel_id}")
                    else:
                        logger.debug(
                            f"Channel {process_info.channel_id} idle for {idle_seconds:.0f}s "
                            f"(threshold: {self.idle_timeout}s)"
                        )
                
                except Exception as e:
                    logger.error(f"Error checking idle status for channel {process_info.channel_id}: {str(e)}")
            
            if stopped_count > 0:
                logger.info(f"Stopped {stopped_count} idle processes")
            else:
                logger.debug("No idle processes found")
                
        except Exception as e:
            logger.error(f"Error during idle process check: {str(e)}")
    
    def get_status(self) -> dict:
        """获取监控器状态"""
        return {
            'running': self.is_running(),
            'idle_timeout': self.idle_timeout,
            'check_interval': self.check_interval,
            'thread_alive': self._thread.is_alive() if self._thread else False
        }