"""
并发控制模块

使用文件锁实现跨进程的并发控制，确保每个频道同时只有一个 FFmpeg 进程运行。
"""

import os
import fcntl
import logging
import time
from typing import Dict, Optional
from pathlib import Path

logger = logging.getLogger(__name__)


class ConcurrencyControl:
    """
    并发控制器
    
    使用文件锁机制确保每个频道同时只有一个进程运行。
    """
    
    def __init__(self, lock_dir: str = "/tmp", lock_timeout: int = 30):
        self.lock_dir = Path(lock_dir)
        self.lock_timeout = lock_timeout
        self.active_locks: Dict[str, int] = {}  # channel_id -> file_descriptor
        
        # 确保锁目录存在
        self.lock_dir.mkdir(parents=True, exist_ok=True)
        
        logger.info(f"ConcurrencyControl initialized with lock_dir={lock_dir}, timeout={lock_timeout}s")
    
    def acquire_lock(self, channel_id: str) -> bool:
        """
        获取频道锁
        
        Args:
            channel_id: 频道 ID
            
        Returns:
            bool: 是否成功获取锁
        """
        if not channel_id:
            raise ValueError("channel_id cannot be empty")
        
        lock_file_path = self._get_lock_file_path(channel_id)
        
        try:
            # 打开锁文件
            fd = os.open(lock_file_path, os.O_CREAT | os.O_WRONLY | os.O_TRUNC, 0o644)
            
            try:
                # 尝试获取非阻塞独占锁
                fcntl.flock(fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
                
                # 写入进程信息
                os.write(fd, f"{os.getpid()}\n{int(time.time())}\n".encode())
                os.fsync(fd)
                
                # 记录活跃锁
                self.active_locks[channel_id] = fd
                
                logger.debug(f"Successfully acquired lock for channel {channel_id}")
                return True
                
            except BlockingIOError:
                # 锁已被其他进程持有
                os.close(fd)
                logger.debug(f"Lock for channel {channel_id} is already held by another process")
                return False
                
        except OSError as e:
            logger.error(f"Failed to acquire lock for channel {channel_id}: {str(e)}")
            return False
    
    def release_lock(self, channel_id: str) -> bool:
        """
        释放频道锁
        
        Args:
            channel_id: 频道 ID
            
        Returns:
            bool: 是否成功释放锁
        """
        if not channel_id:
            return False
        
        if channel_id not in self.active_locks:
            logger.debug(f"No active lock found for channel {channel_id}")
            return False
        
        try:
            fd = self.active_locks[channel_id]
            
            # 释放锁
            fcntl.flock(fd, fcntl.LOCK_UN)
            os.close(fd)
            
            # 删除锁文件
            lock_file_path = self._get_lock_file_path(channel_id)
            try:
                os.remove(lock_file_path)
            except OSError:
                pass  # 文件可能已被删除
            
            # 从活跃锁中移除
            del self.active_locks[channel_id]
            
            logger.debug(f"Successfully released lock for channel {channel_id}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to release lock for channel {channel_id}: {str(e)}")
            return False
    
    def is_locked(self, channel_id: str) -> bool:
        """
        检查频道是否被锁定
        
        Args:
            channel_id: 频道 ID
            
        Returns:
            bool: 是否被锁定
        """
        if not channel_id:
            return False
        
        # 首先检查是否是当前进程持有的锁
        if channel_id in self.active_locks:
            return True
        
        # 检查锁文件是否存在且被其他进程持有
        lock_file_path = self._get_lock_file_path(channel_id)
        
        if not os.path.exists(lock_file_path):
            return False
        
        try:
            # 尝试获取锁来检查是否被持有
            fd = os.open(lock_file_path, os.O_RDONLY)
            try:
                fcntl.flock(fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
                # 能获取锁说明没有被持有，释放并删除文件
                fcntl.flock(fd, fcntl.LOCK_UN)
                os.close(fd)
                os.remove(lock_file_path)
                return False
            except BlockingIOError:
                # 锁被持有
                os.close(fd)
                return True
        except OSError:
            # 文件可能已被删除或无法访问
            return False
    
    def get_lock_info(self, channel_id: str) -> Optional[Dict[str, any]]:
        """
        获取锁信息
        
        Args:
            channel_id: 频道 ID
            
        Returns:
            Dict: 锁信息，包含 pid 和 timestamp
        """
        if not channel_id:
            return None
        
        lock_file_path = self._get_lock_file_path(channel_id)
        
        if not os.path.exists(lock_file_path):
            return None
        
        try:
            with open(lock_file_path, 'r') as f:
                lines = f.read().strip().split('\n')
                if len(lines) >= 2:
                    return {
                        'pid': int(lines[0]),
                        'timestamp': int(lines[1]),
                        'channel_id': channel_id,
                        'lock_file': str(lock_file_path)
                    }
        except (OSError, ValueError) as e:
            logger.debug(f"Failed to read lock info for channel {channel_id}: {str(e)}")
        
        return None
    
    def cleanup_stale_locks(self):
        """清理过期的锁文件"""
        try:
            current_time = time.time()
            cleaned_count = 0
            
            # 遍历所有锁文件
            for lock_file in self.lock_dir.glob("ffmpeg_lock_*.lock"):
                try:
                    # 检查文件是否过期
                    file_mtime = lock_file.stat().st_mtime
                    if current_time - file_mtime > self.lock_timeout:
                        # 尝试获取锁来确认是否过期
                        try:
                            fd = os.open(str(lock_file), os.O_RDONLY)
                            try:
                                fcntl.flock(fd, fcntl.LOCK_EX | fcntl.LOCK_NB)
                                # 能获取锁说明是过期的，删除文件
                                fcntl.flock(fd, fcntl.LOCK_UN)
                                os.close(fd)
                                lock_file.unlink()
                                cleaned_count += 1
                                logger.debug(f"Cleaned up stale lock file: {lock_file}")
                            except BlockingIOError:
                                # 锁仍被持有，不是过期的
                                os.close(fd)
                        except OSError:
                            # 文件可能已被删除
                            pass
                
                except OSError as e:
                    logger.debug(f"Error checking lock file {lock_file}: {str(e)}")
            
            if cleaned_count > 0:
                logger.info(f"Cleaned up {cleaned_count} stale lock files")
            else:
                logger.debug("No stale lock files found")
                
        except Exception as e:
            logger.error(f"Error during stale lock cleanup: {str(e)}")
    
    def list_active_locks(self) -> list:
        """
        列出所有活跃的锁
        
        Returns:
            List: 活跃锁信息列表
        """
        active_locks = []
        
        try:
            for lock_file in self.lock_dir.glob("ffmpeg_lock_*.lock"):
                # 从文件名提取 channel_id
                filename = lock_file.name
                if filename.startswith("ffmpeg_lock_") and filename.endswith(".lock"):
                    channel_id = filename[12:-5]  # 移除前缀和后缀
                    
                    lock_info = self.get_lock_info(channel_id)
                    if lock_info and self.is_locked(channel_id):
                        active_locks.append(lock_info)
        
        except Exception as e:
            logger.error(f"Error listing active locks: {str(e)}")
        
        return active_locks
    
    def _get_lock_file_path(self, channel_id: str) -> str:
        """获取锁文件路径"""
        return str(self.lock_dir / f"ffmpeg_lock_{channel_id}.lock")
    
    def __del__(self):
        """析构函数：释放所有活跃锁"""
        for channel_id in list(self.active_locks.keys()):
            self.release_lock(channel_id)