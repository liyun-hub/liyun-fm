"""
错误处理和恢复机制

实现网络错误检测、磁盘空间监控、进程崩溃恢复等功能。
"""

import os
import shutil
import psutil
import logging
import threading
import time
import subprocess
from typing import Dict, List, Optional, Tuple, Callable
from enum import Enum
from dataclasses import dataclass
from datetime import datetime, timezone

logger = logging.getLogger(__name__)


class ErrorType(Enum):
    """错误类型枚举"""
    NETWORK_ERROR = "network_error"
    DISK_SPACE_ERROR = "disk_space_error"
    PROCESS_CRASH = "process_crash"
    FFMPEG_ERROR = "ffmpeg_error"
    SYSTEM_ERROR = "system_error"


@dataclass
class ErrorInfo:
    """错误信息数据类"""
    error_type: ErrorType
    channel_id: str
    error_message: str
    timestamp: datetime
    recovery_attempted: bool = False
    recovery_successful: bool = False
    additional_info: Optional[Dict] = None


class NetworkErrorDetector:
    """网络错误检测器"""
    
    # 网络错误关键词
    NETWORK_ERROR_PATTERNS = [
        'Connection refused',
        'Connection timed out',
        'Connection reset by peer',
        'Network is unreachable',
        'No route to host',
        'Temporary failure in name resolution',
        'Name or service not known',
        '404 Not Found',
        '403 Forbidden',
        '500 Internal Server Error',
        'HTTP error',
        'Failed to resolve hostname',
        'TLS fatal alert',
        'SSL connection error',
        'Input/output error',
        'Protocol error',
        'Server returned 4XX',
        'Server returned 5XX',
        'Invalid data found when processing input',
        'End of file'
    ]
    
    @classmethod
    def is_network_error(cls, error_message: str) -> bool:
        """检测是否为网络错误"""
        if not error_message:
            return False
        
        error_lower = error_message.lower()
        return any(pattern.lower() in error_lower for pattern in cls.NETWORK_ERROR_PATTERNS)
    
    @classmethod
    def extract_network_error_details(cls, error_message: str) -> Dict[str, str]:
        """提取网络错误详细信息"""
        details = {
            'error_category': 'network',
            'original_message': error_message
        }
        
        error_lower = error_message.lower()
        
        if any(pattern in error_lower for pattern in ['connection refused', 'connection timed out']):
            details['error_subtype'] = 'connection_failed'
            details['suggested_action'] = 'Check stream URL and network connectivity'
        elif any(pattern in error_lower for pattern in ['404', '403', '500']):
            details['error_subtype'] = 'http_error'
            details['suggested_action'] = 'Verify stream URL and server status'
        elif any(pattern in error_lower for pattern in ['name resolution', 'hostname']):
            details['error_subtype'] = 'dns_error'
            details['suggested_action'] = 'Check DNS configuration and hostname'
        elif any(pattern in error_lower for pattern in ['ssl', 'tls']):
            details['error_subtype'] = 'ssl_error'
            details['suggested_action'] = 'Check SSL/TLS configuration'
        else:
            details['error_subtype'] = 'general_network'
            details['suggested_action'] = 'Check network connectivity and stream availability'
        
        return details


class DiskSpaceMonitor:
    """磁盘空间监控器"""
    
    def __init__(self, hls_output_dir: str, min_free_space_mb: int = 500):
        self.hls_output_dir = hls_output_dir
        self.min_free_space_mb = min_free_space_mb
        self.min_free_space_bytes = min_free_space_mb * 1024 * 1024
        
    def check_disk_space(self) -> Tuple[bool, Dict[str, int]]:
        """
        检查磁盘空间
        
        Returns:
            Tuple[bool, Dict]: (是否有足够空间, 磁盘信息)
        """
        try:
            disk_usage = shutil.disk_usage(self.hls_output_dir)
            free_space_bytes = disk_usage.free
            free_space_mb = free_space_bytes // (1024 * 1024)
            
            disk_info = {
                'total_mb': disk_usage.total // (1024 * 1024),
                'used_mb': disk_usage.used // (1024 * 1024),
                'free_mb': free_space_mb,
                'free_percent': (free_space_bytes / disk_usage.total) * 100
            }
            
            has_enough_space = free_space_bytes >= self.min_free_space_bytes
            
            if not has_enough_space:
                logger.warning(f"Low disk space: {free_space_mb}MB free, minimum required: {self.min_free_space_mb}MB")
            
            return has_enough_space, disk_info
            
        except Exception as e:
            logger.error(f"Failed to check disk space: {str(e)}")
            return False, {}
    
    def get_directory_size(self, directory: str) -> int:
        """获取目录大小（字节）"""
        try:
            total_size = 0
            for dirpath, dirnames, filenames in os.walk(directory):
                for filename in filenames:
                    filepath = os.path.join(dirpath, filename)
                    try:
                        total_size += os.path.getsize(filepath)
                    except (OSError, FileNotFoundError):
                        continue
            return total_size
        except Exception as e:
            logger.error(f"Failed to calculate directory size for {directory}: {str(e)}")
            return 0
    
    def cleanup_old_files(self, max_age_seconds: int = 3600) -> Dict[str, int]:
        """
        清理旧文件以释放空间
        
        Args:
            max_age_seconds: 文件最大保留时间（秒）
            
        Returns:
            Dict: 清理统计信息
        """
        cleanup_stats = {
            'files_deleted': 0,
            'bytes_freed': 0,
            'directories_removed': 0
        }
        
        try:
            current_time = time.time()
            
            for root, dirs, files in os.walk(self.hls_output_dir, topdown=False):
                # 清理文件
                for file in files:
                    file_path = os.path.join(root, file)
                    try:
                        file_stat = os.stat(file_path)
                        if current_time - file_stat.st_mtime > max_age_seconds:
                            file_size = file_stat.st_size
                            os.remove(file_path)
                            cleanup_stats['files_deleted'] += 1
                            cleanup_stats['bytes_freed'] += file_size
                            logger.debug(f"Deleted old file: {file_path}")
                    except (OSError, FileNotFoundError):
                        continue
                
                # 清理空目录
                for dir_name in dirs:
                    dir_path = os.path.join(root, dir_name)
                    try:
                        if not os.listdir(dir_path):  # 空目录
                            os.rmdir(dir_path)
                            cleanup_stats['directories_removed'] += 1
                            logger.debug(f"Removed empty directory: {dir_path}")
                    except (OSError, FileNotFoundError):
                        continue
            
            if cleanup_stats['files_deleted'] > 0:
                freed_mb = cleanup_stats['bytes_freed'] // (1024 * 1024)
                logger.info(f"Emergency cleanup completed: {cleanup_stats['files_deleted']} files deleted, {freed_mb}MB freed")
            
            return cleanup_stats
            
        except Exception as e:
            logger.error(f"Failed to cleanup old files: {str(e)}")
            return cleanup_stats


class ProcessCrashDetector:
    """进程崩溃检测器"""
    
    @staticmethod
    def is_process_alive(pid: int) -> bool:
        """检查进程是否存活"""
        try:
            return psutil.pid_exists(pid)
        except Exception:
            return False
    
    @staticmethod
    def get_process_info(pid: int) -> Optional[Dict]:
        """获取进程信息"""
        try:
            process = psutil.Process(pid)
            return {
                'pid': pid,
                'name': process.name(),
                'status': process.status(),
                'create_time': process.create_time(),
                'cpu_percent': process.cpu_percent(),
                'memory_info': process.memory_info()._asdict(),
                'cmdline': process.cmdline()
            }
        except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
            return None
    
    @staticmethod
    def kill_process_tree(pid: int, timeout: int = 5) -> bool:
        """终止进程树"""
        try:
            parent = psutil.Process(pid)
            children = parent.children(recursive=True)
            
            # 先终止子进程
            for child in children:
                try:
                    child.terminate()
                except (psutil.NoSuchProcess, psutil.AccessDenied):
                    continue
            
            # 终止父进程
            parent.terminate()
            
            # 等待进程终止
            gone, alive = psutil.wait_procs(children + [parent], timeout=timeout)
            
            # 强制杀死仍然存活的进程
            for proc in alive:
                try:
                    proc.kill()
                except (psutil.NoSuchProcess, psutil.AccessDenied):
                    continue
            
            return True
            
        except (psutil.NoSuchProcess, psutil.AccessDenied):
            return True  # 进程已经不存在
        except Exception as e:
            logger.error(f"Failed to kill process tree {pid}: {str(e)}")
            return False


class ErrorHandler:
    """错误处理和恢复机制主类"""
    
    def __init__(self, hls_output_dir: str, min_free_space_mb: int = 500):
        self.hls_output_dir = hls_output_dir
        self.disk_monitor = DiskSpaceMonitor(hls_output_dir, min_free_space_mb)
        self.error_history: List[ErrorInfo] = []
        self.recovery_callbacks: Dict[ErrorType, List[Callable]] = {
            ErrorType.NETWORK_ERROR: [],
            ErrorType.DISK_SPACE_ERROR: [],
            ErrorType.PROCESS_CRASH: [],
            ErrorType.FFMPEG_ERROR: [],
            ErrorType.SYSTEM_ERROR: []
        }
        self.lock = threading.RLock()
        
        logger.info("ErrorHandler initialized")
    
    def register_recovery_callback(self, error_type: ErrorType, callback: Callable):
        """注册恢复回调函数"""
        with self.lock:
            self.recovery_callbacks[error_type].append(callback)
            logger.debug(f"Registered recovery callback for {error_type.value}")
    
    def handle_error(self, channel_id: str, error_message: str, 
                    additional_context: Optional[Dict] = None) -> ErrorInfo:
        """
        处理错误
        
        Args:
            channel_id: 频道 ID
            error_message: 错误消息
            additional_context: 额外上下文信息
            
        Returns:
            ErrorInfo: 错误信息对象
        """
        # 检测错误类型
        error_type = self._detect_error_type(error_message, additional_context)
        
        # 创建错误信息
        error_info = ErrorInfo(
            error_type=error_type,
            channel_id=channel_id,
            error_message=error_message,
            timestamp=datetime.now(timezone.utc),
            additional_info=additional_context
        )
        
        # 记录错误
        with self.lock:
            self.error_history.append(error_info)
            # 保持错误历史记录在合理范围内
            if len(self.error_history) > 1000:
                self.error_history = self.error_history[-500:]
        
        logger.error(f"Error detected for channel {channel_id}: {error_type.value} - {error_message}")
        
        # 尝试恢复
        self._attempt_recovery(error_info)
        
        return error_info
    
    def _detect_error_type(self, error_message: str, context: Optional[Dict] = None) -> ErrorType:
        """检测错误类型"""
        if NetworkErrorDetector.is_network_error(error_message):
            return ErrorType.NETWORK_ERROR
        
        # 检查是否为磁盘空间错误
        disk_error_patterns = [
            'No space left on device',
            'Disk full',
            'Cannot write',
            'Permission denied'
        ]
        
        if any(pattern.lower() in error_message.lower() for pattern in disk_error_patterns):
            return ErrorType.DISK_SPACE_ERROR
        
        # 检查是否为进程崩溃
        if context and context.get('process_crashed'):
            return ErrorType.PROCESS_CRASH
        
        # 检查是否为 FFmpeg 特定错误
        ffmpeg_error_patterns = [
            'Invalid data found when processing input',
            'Decoder (codec',
            'Stream mapping',
            'Output file is empty',
            'Conversion failed'
        ]
        
        if any(pattern.lower() in error_message.lower() for pattern in ffmpeg_error_patterns):
            return ErrorType.FFMPEG_ERROR
        
        return ErrorType.SYSTEM_ERROR
    
    def _attempt_recovery(self, error_info: ErrorInfo):
        """尝试错误恢复"""
        error_info.recovery_attempted = True
        
        try:
            if error_info.error_type == ErrorType.NETWORK_ERROR:
                success = self._recover_network_error(error_info)
            elif error_info.error_type == ErrorType.DISK_SPACE_ERROR:
                success = self._recover_disk_space_error(error_info)
            elif error_info.error_type == ErrorType.PROCESS_CRASH:
                success = self._recover_process_crash(error_info)
            elif error_info.error_type == ErrorType.FFMPEG_ERROR:
                success = self._recover_ffmpeg_error(error_info)
            else:
                success = self._recover_system_error(error_info)
            
            error_info.recovery_successful = success
            
            if success:
                logger.info(f"Successfully recovered from {error_info.error_type.value} for channel {error_info.channel_id}")
            else:
                logger.warning(f"Failed to recover from {error_info.error_type.value} for channel {error_info.channel_id}")
            
            # 调用注册的恢复回调
            self._call_recovery_callbacks(error_info)
            
        except Exception as e:
            logger.error(f"Error during recovery attempt: {str(e)}")
            error_info.recovery_successful = False
    
    def _recover_network_error(self, error_info: ErrorInfo) -> bool:
        """恢复网络错误"""
        logger.info(f"Attempting network error recovery for channel {error_info.channel_id}")
        
        # 网络错误通常需要等待一段时间后重试
        # 这里主要是清理资源，实际重试由上层逻辑处理
        
        # 提取网络错误详细信息
        network_details = NetworkErrorDetector.extract_network_error_details(error_info.error_message)
        error_info.additional_info = error_info.additional_info or {}
        error_info.additional_info.update(network_details)
        
        # 对于网络错误，主要是记录和清理，不进行自动重试
        return True
    
    def _recover_disk_space_error(self, error_info: ErrorInfo) -> bool:
        """恢复磁盘空间错误"""
        logger.info(f"Attempting disk space error recovery for channel {error_info.channel_id}")
        
        try:
            # 检查当前磁盘空间
            has_space, disk_info = self.disk_monitor.check_disk_space()
            
            if not has_space:
                # 执行紧急清理
                logger.info("Performing emergency cleanup due to low disk space")
                cleanup_stats = self.disk_monitor.cleanup_old_files(max_age_seconds=1800)  # 清理30分钟前的文件
                
                # 再次检查磁盘空间
                has_space_after, disk_info_after = self.disk_monitor.check_disk_space()
                
                error_info.additional_info = error_info.additional_info or {}
                error_info.additional_info.update({
                    'disk_info_before': disk_info,
                    'cleanup_stats': cleanup_stats,
                    'disk_info_after': disk_info_after,
                    'recovery_successful': has_space_after
                })
                
                return has_space_after
            
            return True
            
        except Exception as e:
            logger.error(f"Failed to recover from disk space error: {str(e)}")
            return False
    
    def _recover_process_crash(self, error_info: ErrorInfo) -> bool:
        """恢复进程崩溃"""
        logger.info(f"Attempting process crash recovery for channel {error_info.channel_id}")
        
        try:
            # 进程崩溃恢复主要是清理残留资源
            # 实际的进程重启由上层逻辑处理
            
            context = error_info.additional_info or {}
            crashed_pid = context.get('crashed_pid')
            
            if crashed_pid:
                # 确保进程完全终止
                if ProcessCrashDetector.is_process_alive(crashed_pid):
                    logger.warning(f"Crashed process {crashed_pid} is still alive, attempting to kill it")
                    ProcessCrashDetector.kill_process_tree(crashed_pid)
                
                # 清理进程相关的临时文件
                channel_output_dir = os.path.join(self.hls_output_dir, error_info.channel_id)
                if os.path.exists(channel_output_dir):
                    try:
                        # 清理可能损坏的文件
                        for file in os.listdir(channel_output_dir):
                            file_path = os.path.join(channel_output_dir, file)
                            if os.path.isfile(file_path):
                                # 检查文件是否可能损坏（大小为0或修改时间很近）
                                stat = os.stat(file_path)
                                if stat.st_size == 0 or (time.time() - stat.st_mtime) < 10:
                                    os.remove(file_path)
                                    logger.debug(f"Removed potentially corrupted file: {file_path}")
                    except Exception as e:
                        logger.warning(f"Failed to cleanup corrupted files: {str(e)}")
            
            return True
            
        except Exception as e:
            logger.error(f"Failed to recover from process crash: {str(e)}")
            return False
    
    def _recover_ffmpeg_error(self, error_info: ErrorInfo) -> bool:
        """恢复 FFmpeg 错误"""
        logger.info(f"Attempting FFmpeg error recovery for channel {error_info.channel_id}")
        
        # FFmpeg 错误通常需要重新启动进程
        # 这里主要是分析错误原因和清理资源
        
        error_message = error_info.error_message.lower()
        
        # 分析 FFmpeg 错误类型
        if 'invalid data' in error_message:
            error_subtype = 'invalid_input_data'
            suggested_action = 'Check input stream format and codec compatibility'
        elif 'decoder' in error_message:
            error_subtype = 'decoder_error'
            suggested_action = 'Verify input stream codec and format'
        elif 'stream mapping' in error_message:
            error_subtype = 'stream_mapping_error'
            suggested_action = 'Check FFmpeg command parameters'
        else:
            error_subtype = 'general_ffmpeg_error'
            suggested_action = 'Review FFmpeg logs for detailed error information'
        
        error_info.additional_info = error_info.additional_info or {}
        error_info.additional_info.update({
            'ffmpeg_error_subtype': error_subtype,
            'suggested_action': suggested_action
        })
        
        return True
    
    def _recover_system_error(self, error_info: ErrorInfo) -> bool:
        """恢复系统错误"""
        logger.info(f"Attempting system error recovery for channel {error_info.channel_id}")
        
        # 系统错误的恢复策略比较通用
        # 主要是记录错误信息，便于后续分析
        
        return True
    
    def _call_recovery_callbacks(self, error_info: ErrorInfo):
        """调用恢复回调函数"""
        callbacks = self.recovery_callbacks.get(error_info.error_type, [])
        
        for callback in callbacks:
            try:
                callback(error_info)
            except Exception as e:
                logger.error(f"Error in recovery callback: {str(e)}")
    
    def check_system_health(self) -> Dict[str, any]:
        """检查系统健康状态"""
        health_status = {
            'timestamp': datetime.now(timezone.utc).isoformat(),
            'overall_status': 'healthy',
            'issues': []
        }
        
        try:
            # 检查磁盘空间
            has_space, disk_info = self.disk_monitor.check_disk_space()
            health_status['disk_space'] = disk_info
            
            if not has_space:
                health_status['overall_status'] = 'warning'
                health_status['issues'].append({
                    'type': 'disk_space',
                    'severity': 'warning',
                    'message': f"Low disk space: {disk_info.get('free_mb', 0)}MB available"
                })
            
            # 检查最近的错误
            recent_errors = self.get_recent_errors(minutes=30)
            if len(recent_errors) > 10:  # 30分钟内超过10个错误
                health_status['overall_status'] = 'warning'
                health_status['issues'].append({
                    'type': 'high_error_rate',
                    'severity': 'warning',
                    'message': f"High error rate: {len(recent_errors)} errors in last 30 minutes"
                })
            
            # 检查系统资源
            cpu_percent = psutil.cpu_percent(interval=1)
            memory = psutil.virtual_memory()
            
            health_status['system_resources'] = {
                'cpu_percent': cpu_percent,
                'memory_percent': memory.percent,
                'memory_available_mb': memory.available // (1024 * 1024)
            }
            
            if cpu_percent > 90:
                health_status['overall_status'] = 'warning'
                health_status['issues'].append({
                    'type': 'high_cpu',
                    'severity': 'warning',
                    'message': f"High CPU usage: {cpu_percent}%"
                })
            
            if memory.percent > 90:
                health_status['overall_status'] = 'warning'
                health_status['issues'].append({
                    'type': 'high_memory',
                    'severity': 'warning',
                    'message': f"High memory usage: {memory.percent}%"
                })
            
        except Exception as e:
            health_status['overall_status'] = 'error'
            health_status['issues'].append({
                'type': 'health_check_error',
                'severity': 'error',
                'message': f"Failed to perform health check: {str(e)}"
            })
        
        return health_status
    
    def get_recent_errors(self, minutes: int = 60) -> List[ErrorInfo]:
        """获取最近的错误"""
        cutoff_time = datetime.now(timezone.utc).timestamp() - (minutes * 60)
        
        with self.lock:
            return [
                error for error in self.error_history
                if error.timestamp.timestamp() > cutoff_time
            ]
    
    def get_error_statistics(self) -> Dict[str, any]:
        """获取错误统计信息"""
        with self.lock:
            total_errors = len(self.error_history)
            
            if total_errors == 0:
                return {
                    'total_errors': 0,
                    'error_types': {},
                    'recovery_rate': 0.0,
                    'recent_errors': 0
                }
            
            # 按错误类型统计
            error_types = {}
            recovery_count = 0
            
            for error in self.error_history:
                error_type = error.error_type.value
                error_types[error_type] = error_types.get(error_type, 0) + 1
                
                if error.recovery_successful:
                    recovery_count += 1
            
            # 最近1小时的错误
            recent_errors = len(self.get_recent_errors(60))
            
            return {
                'total_errors': total_errors,
                'error_types': error_types,
                'recovery_rate': (recovery_count / total_errors) * 100,
                'recent_errors': recent_errors,
                'recovery_attempts': sum(1 for e in self.error_history if e.recovery_attempted),
                'successful_recoveries': recovery_count
            }