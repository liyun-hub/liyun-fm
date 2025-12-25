"""
资源清理器

定期清理过期的 HLS 切片、空目录、残留锁文件等资源。
"""

import os
import threading
import time
import logging
from pathlib import Path
from typing import List

logger = logging.getLogger(__name__)


class ResourceCleaner:
    """
    资源清理器
    
    定期清理系统资源，包括：
    - 过期的 HLS 切片文件
    - 空的频道目录
    - 残留的锁文件
    - 过期的日志文件
    """
    
    def __init__(self, hls_output_dir: str, cleanup_interval: int = 180, max_age: int = 720):
        self.hls_output_dir = Path(hls_output_dir)
        self.cleanup_interval = cleanup_interval  # 清理间隔（秒）
        self.max_age = max_age  # 文件最大保留时间（秒）
        
        self._running = False
        self._thread: threading.Thread = None
        self._stop_event = threading.Event()
        
        logger.info(f"ResourceCleaner initialized with cleanup_interval={cleanup_interval}s, max_age={max_age}s")
    
    def start(self):
        """启动清理器"""
        if self._running:
            logger.warning("ResourceCleaner is already running")
            return
        
        self._running = True
        self._stop_event.clear()
        
        self._thread = threading.Thread(
            target=self._cleanup_loop,
            name="ResourceCleaner",
            daemon=True
        )
        self._thread.start()
        
        logger.info("ResourceCleaner started")
    
    def stop(self):
        """停止清理器"""
        if not self._running:
            logger.warning("ResourceCleaner is not running")
            return
        
        logger.info("Stopping ResourceCleaner...")
        
        self._running = False
        self._stop_event.set()
        
        if self._thread and self._thread.is_alive():
            self._thread.join(timeout=5)
            if self._thread.is_alive():
                logger.warning("ResourceCleaner thread did not stop gracefully")
        
        logger.info("ResourceCleaner stopped")
    
    def is_running(self) -> bool:
        """检查清理器是否在运行"""
        return self._running and self._thread and self._thread.is_alive()
    
    def _cleanup_loop(self):
        """清理循环"""
        logger.info("ResourceCleaner loop started")
        
        while self._running and not self._stop_event.is_set():
            try:
                self._perform_cleanup()
            except Exception as e:
                logger.error(f"Error during resource cleanup: {str(e)}")
            
            # 等待下一次清理
            if self._stop_event.wait(timeout=self.cleanup_interval):
                break
        
        logger.info("ResourceCleaner loop stopped")
    
    def _perform_cleanup(self):
        """执行清理操作"""
        logger.debug("Starting resource cleanup")
        
        start_time = time.time()
        
        # 清理 HLS 切片
        hls_stats = self._cleanup_hls_segments()
        
        # 清理空目录
        empty_dirs = self._cleanup_empty_directories()
        
        # 清理残留锁文件（这个应该由 ConcurrencyControl 处理，但作为备份）
        # lock_files = self._cleanup_stale_locks()
        
        cleanup_time = time.time() - start_time
        
        logger.info(
            f"Resource cleanup completed in {cleanup_time:.2f}s: "
            f"deleted {hls_stats['deleted_files']} HLS files, "
            f"removed {empty_dirs} empty directories"
        )
    
    def _cleanup_hls_segments(self) -> dict:
        """清理 HLS 切片文件"""
        stats = {
            'deleted_files': 0,
            'total_files': 0,
            'errors': 0
        }
        
        if not self.hls_output_dir.exists():
            logger.debug(f"HLS output directory {self.hls_output_dir} does not exist")
            return stats
        
        current_time = time.time()
        
        try:
            # 遍历所有频道目录
            for channel_dir in self.hls_output_dir.iterdir():
                if not channel_dir.is_dir():
                    continue
                
                try:
                    # 遍历频道目录中的文件
                    for file_path in channel_dir.iterdir():
                        if not file_path.is_file():
                            continue
                        
                        stats['total_files'] += 1
                        
                        try:
                            # 检查文件类型和年龄
                            if self._should_delete_hls_file(file_path, current_time):
                                file_path.unlink()
                                stats['deleted_files'] += 1
                                logger.debug(f"Deleted HLS file: {file_path}")
                        
                        except OSError as e:
                            stats['errors'] += 1
                            logger.error(f"Failed to delete HLS file {file_path}: {str(e)}")
                
                except OSError as e:
                    stats['errors'] += 1
                    logger.error(f"Failed to process channel directory {channel_dir}: {str(e)}")
        
        except OSError as e:
            stats['errors'] += 1
            logger.error(f"Failed to list HLS output directory: {str(e)}")
        
        return stats
    
    def _should_delete_hls_file(self, file_path: Path, current_time: float) -> bool:
        """判断是否应该删除 HLS 文件"""
        filename = file_path.name
        
        # 保留播放列表文件
        if filename.endswith('.m3u8'):
            return False
        
        # 删除非预期格式的文件
        if not (filename.startswith('segment_') and filename.endswith('.ts')):
            logger.debug(f"Deleting invalid HLS file: {filename}")
            return True
        
        # 检查文件年龄
        try:
            file_mtime = file_path.stat().st_mtime
            file_age = current_time - file_mtime
            
            if file_age > self.max_age:
                logger.debug(f"Deleting expired HLS file: {filename} (age: {file_age:.0f}s)")
                return True
        
        except OSError as e:
            logger.error(f"Failed to get file stats for {file_path}: {str(e)}")
            return False
        
        return False
    
    def _cleanup_empty_directories(self) -> int:
        """清理空目录"""
        removed_count = 0
        
        if not self.hls_output_dir.exists():
            return removed_count
        
        try:
            # 遍历所有频道目录
            for channel_dir in self.hls_output_dir.iterdir():
                if not channel_dir.is_dir():
                    continue
                
                try:
                    # 检查目录是否为空
                    if not any(channel_dir.iterdir()):
                        channel_dir.rmdir()
                        removed_count += 1
                        logger.debug(f"Removed empty directory: {channel_dir}")
                
                except OSError as e:
                    logger.error(f"Failed to remove empty directory {channel_dir}: {str(e)}")
        
        except OSError as e:
            logger.error(f"Failed to list directories for cleanup: {str(e)}")
        
        return removed_count
    
    def get_status(self) -> dict:
        """获取清理器状态"""
        return {
            'running': self.is_running(),
            'cleanup_interval': self.cleanup_interval,
            'max_age': self.max_age,
            'hls_output_dir': str(self.hls_output_dir),
            'thread_alive': self._thread.is_alive() if self._thread else False
        }
    
    def force_cleanup(self) -> dict:
        """强制执行一次清理"""
        logger.info("Performing forced cleanup")
        
        try:
            self._perform_cleanup()
            return {'success': True, 'message': 'Cleanup completed successfully'}
        except Exception as e:
            error_msg = f"Forced cleanup failed: {str(e)}"
            logger.error(error_msg)
            return {'success': False, 'message': error_msg}