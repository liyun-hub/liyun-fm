"""
FFmpeg 进程管理器 - 重构版本

基于设计文档实现的新版本，提供清晰的职责分离和更好的错误处理。
"""

import subprocess
import threading
import time
import logging
import os
from datetime import datetime, timezone
from typing import Dict, Optional, List
from dataclasses import dataclass
from enum import Enum

from app.concurrency_control import ConcurrencyControl
from app.config import config
from app.error_handler import ErrorHandler, ErrorType

logger = logging.getLogger(__name__)


class ProcessStatus(Enum):
    """进程状态枚举"""
    RUNNING = "running"
    STOPPED = "stopped"
    ERROR = "error"
    STARTING = "starting"


@dataclass
class ProcessInfo:
    """进程信息数据类"""
    channel_id: str
    pid: Optional[int]
    status: ProcessStatus
    stream_url: str
    start_time: datetime
    last_activity_time: datetime
    error_message: Optional[str] = None
    hls_output_dir: Optional[str] = None


class ProcessManager:
    """
    FFmpeg 进程管理器
    
    职责：
    - 管理 FFmpeg 进程的生命周期
    - 跟踪进程状态和错误
    - 提供进程查询接口
    """
    
    def __init__(self, concurrency_control: ConcurrencyControl, error_handler: Optional[ErrorHandler] = None):
        self.concurrency_control = concurrency_control
        self.error_handler = error_handler
        self.processes: Dict[str, ProcessInfo] = {}
        self.subprocess_handles: Dict[str, subprocess.Popen] = {}
        self.lock = threading.RLock()
        
        # 清理残留进程和锁文件
        self._cleanup_on_startup()
        
        logger.info("ProcessManager initialized")
    
    def _cleanup_on_startup(self):
        """启动时清理残留的进程和锁文件"""
        try:
            logger.info("Cleaning up residual processes and lock files...")
            
            # 清理残留的锁文件
            self.concurrency_control.cleanup_stale_locks()
            
            # 清理残留的 FFmpeg 进程
            self._cleanup_existing_ffmpeg_processes()
            
            logger.info("Startup cleanup completed")
        except Exception as e:
            logger.error(f"Error during startup cleanup: {str(e)}")
    
    def _cleanup_existing_ffmpeg_processes(self):
        """清理系统中现有的 FFmpeg 进程"""
        try:
            import psutil
            killed_count = 0
            
            for proc in psutil.process_iter(['pid', 'name', 'cmdline']):
                try:
                    if proc.info['name'] == 'ffmpeg':
                        # 检查是否是我们的 HLS 输出进程
                        cmdline = ' '.join(proc.info.get('cmdline', []))
                        if config.HLS_OUTPUT_DIR in cmdline:
                            logger.info(f"Killing residual FFmpeg process PID {proc.info['pid']}")
                            proc.terminate()
                            try:
                                proc.wait(timeout=5)
                            except psutil.TimeoutExpired:
                                proc.kill()
                            killed_count += 1
                except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
                    continue
            
            if killed_count > 0:
                logger.info(f"Cleaned up {killed_count} residual FFmpeg processes")
            else:
                logger.info("No residual FFmpeg processes found")
                
        except ImportError:
            logger.warning("psutil not available, skipping FFmpeg process cleanup")
        except Exception as e:
            logger.error(f"Error cleaning up FFmpeg processes: {str(e)}")
    
    def start_process(self, channel_id: str, stream_url: str) -> ProcessInfo:
        """
        启动 FFmpeg 进程
        
        Args:
            channel_id: 频道 ID
            stream_url: 音频流 URL
            
        Returns:
            ProcessInfo: 进程信息
            
        Raises:
            ValueError: 参数无效
            RuntimeError: 进程启动失败
            ProcessAlreadyRunningError: 进程已在运行
        """
        if not channel_id or not stream_url:
            raise ValueError("channel_id and stream_url are required")
        
        with self.lock:
            # 检查进程是否已在运行
            if self._is_process_running_internal(channel_id):
                existing_info = self.processes.get(channel_id)
                if existing_info:
                    raise ProcessAlreadyRunningError(f"Process for channel {channel_id} is already running")
            
            # 尝试获取并发控制锁
            if not self.concurrency_control.acquire_lock(channel_id):
                raise ProcessAlreadyRunningError(f"Another process is already handling channel {channel_id}")
            
            try:
                # 创建进程信息
                now = datetime.now(timezone.utc)
                process_info = ProcessInfo(
                    channel_id=channel_id,
                    pid=None,
                    status=ProcessStatus.STARTING,
                    stream_url=stream_url,
                    start_time=now,
                    last_activity_time=now,
                    hls_output_dir=os.path.join(config.HLS_OUTPUT_DIR, channel_id)
                )
                
                self.processes[channel_id] = process_info
                
                # 创建 HLS 输出目录
                os.makedirs(process_info.hls_output_dir, exist_ok=True)
                
                # 构建 FFmpeg 命令
                command = self._build_ffmpeg_command(channel_id, stream_url, process_info.hls_output_dir)
                
                # 启动进程
                logger.info(f"Starting FFmpeg process for channel {channel_id}")
                logger.debug(f"FFmpeg command: {' '.join(command)}")
                
                process = subprocess.Popen(
                    command,
                    stdout=subprocess.DEVNULL,
                    stderr=subprocess.PIPE,
                    bufsize=0
                )
                
                # 等待进程初始化
                time.sleep(1)  # 减少等待时间到1秒
                
                # 检查进程是否立即退出
                if process.poll() is not None:
                    stderr_output = process.stderr.read().decode('utf-8', errors='replace')
                    error_msg = self._parse_ffmpeg_error(stderr_output)
                    
                    # 使用错误处理器处理错误
                    if self.error_handler:
                        self.error_handler.handle_error(
                            channel_id=channel_id,
                            error_message=error_msg,
                            additional_context={
                                'process_start_failed': True,
                                'stderr_output': stderr_output,
                                'stream_url': stream_url
                            }
                        )
                    
                    # 清理资源
                    self.concurrency_control.release_lock(channel_id)
                    del self.processes[channel_id]
                    
                    raise RuntimeError(f"FFmpeg process failed to start: {error_msg}")
                
                # 更新进程信息
                process_info.pid = process.pid
                process_info.status = ProcessStatus.RUNNING
                self.subprocess_handles[channel_id] = process
                
                # 启动监控线程
                self._start_process_monitor(channel_id, process)
                
                logger.info(f"FFmpeg process started successfully for channel {channel_id}, PID: {process.pid}")
                return process_info
                
            except Exception as e:
                # 清理资源
                self.concurrency_control.release_lock(channel_id)
                if channel_id in self.processes:
                    del self.processes[channel_id]
                raise
    
    def stop_process(self, channel_id: str) -> bool:
        """
        停止 FFmpeg 进程
        
        Args:
            channel_id: 频道 ID
            
        Returns:
            bool: 是否成功停止
        """
        with self.lock:
            if channel_id not in self.processes:
                logger.warning(f"Process for channel {channel_id} not found")
                return False
            
            process_info = self.processes[channel_id]
            subprocess_handle = self.subprocess_handles.get(channel_id)
            
            if subprocess_handle and subprocess_handle.poll() is None:
                logger.info(f"Stopping FFmpeg process for channel {channel_id}, PID: {subprocess_handle.pid}")
                
                # 优雅地终止进程
                try:
                    subprocess_handle.terminate()
                    subprocess_handle.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    logger.warning(f"Process {subprocess_handle.pid} did not terminate gracefully, killing it")
                    subprocess_handle.kill()
                    subprocess_handle.wait()
                except Exception as e:
                    logger.error(f"Error stopping process {subprocess_handle.pid}: {str(e)}")
            
            # 更新状态
            process_info.status = ProcessStatus.STOPPED
            
            # 清理资源
            self._cleanup_process_resources(channel_id)
            
            logger.info(f"FFmpeg process for channel {channel_id} stopped")
            return True
    
    def get_process_status(self, channel_id: str) -> Optional[ProcessInfo]:
        """
        获取进程状态
        
        Args:
            channel_id: 频道 ID
            
        Returns:
            ProcessInfo: 进程信息，如果不存在返回 None
        """
        with self.lock:
            if channel_id not in self.processes:
                return None
            
            process_info = self.processes[channel_id]
            
            # 检查进程是否仍在运行
            if process_info.status == ProcessStatus.RUNNING:
                subprocess_handle = self.subprocess_handles.get(channel_id)
                if subprocess_handle and subprocess_handle.poll() is not None:
                    # 进程已终止，更新状态
                    process_info.status = ProcessStatus.STOPPED
            
            return process_info
    
    def list_processes(self) -> List[ProcessInfo]:
        """
        列出所有进程
        
        Returns:
            List[ProcessInfo]: 进程信息列表
        """
        with self.lock:
            # 更新所有进程状态
            for channel_id in list(self.processes.keys()):
                self.get_process_status(channel_id)
            
            return list(self.processes.values())
    
    def is_running(self, channel_id: str) -> bool:
        """
        检查进程是否在运行
        
        Args:
            channel_id: 频道 ID
            
        Returns:
            bool: 是否在运行
        """
        with self.lock:
            return self._is_process_running_internal(channel_id)
    
    def update_activity_time(self, channel_id: str):
        """
        更新进程活动时间
        
        Args:
            channel_id: 频道 ID
        """
        with self.lock:
            if channel_id in self.processes:
                self.processes[channel_id].last_activity_time = datetime.now(timezone.utc)
    
    def _is_process_running_internal(self, channel_id: str) -> bool:
        """内部方法：检查进程是否在运行（不加锁）"""
        if channel_id not in self.processes:
            return False
        
        process_info = self.processes[channel_id]
        if process_info.status != ProcessStatus.RUNNING:
            return False
        
        subprocess_handle = self.subprocess_handles.get(channel_id)
        if subprocess_handle and subprocess_handle.poll() is None:
            return True
        
        # 进程已终止，更新状态
        process_info.status = ProcessStatus.STOPPED
        return False
    
    def _build_ffmpeg_command(self, channel_id: str, stream_url: str, output_dir: str) -> List[str]:
        """构建 FFmpeg 命令"""
        playlist_path = os.path.join(output_dir, config.HLS_PLAYLIST_NAME)
        segment_pattern = os.path.join(output_dir, 'segment_%03d.ts')
        
        command = [
            config.FFMPEG_PATH,
            '-loglevel', 'warning',
            '-i', stream_url,
            '-c:a', config.FFMPEG_AUDIO_CODEC,
            '-b:a', config.FFMPEG_BITRATE,
            '-f', 'hls',
            '-hls_time', str(config.HLS_SEGMENT_DURATION),
            '-hls_list_size', str(config.HLS_SEGMENT_LIST_SIZE),
            '-hls_segment_filename', segment_pattern,
            '-hls_flags', 'delete_segments+program_date_time+independent_segments+split_by_time',
            '-hls_delete_threshold', '6',  # 匹配新的列表大小，保留足够切片
            '-hls_allow_cache', '0',  # 禁用缓存，确保实时性
            '-copyts',
            '-fflags', '+discardcorrupt+igndts+genpts+flush_packets',  # 移除nobuffer，保证稳定性
            '-max_muxing_queue_size', '512',  # 适中的队列大小
            '-user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            '-avoid_negative_ts', 'make_zero',
            '-reconnect', '1',
            '-reconnect_streamed', '1',
            '-reconnect_delay_max', '2',  # 适中的重连延迟
            '-timeout', '5',  # 适中的超时时间
            '-probesize', '1000000',  # 适中的探测大小
            '-analyzeduration', '1000000',  # 适中的分析时间
            '-bufsize', '128k',  # 适中的缓冲区大小
            '-maxrate', '160k',
            '-minrate', '96k',
            '-flags', '+global_header+low_delay',  # 保持低延迟标志
            '-mpegts_flags', 'initial_discontinuity',
            '-hls_start_number_source', 'datetime',
            '-start_number', '0',
            '-flush_packets', '1',  # 立即刷新数据包
            '-preset', 'fast',  # 平衡编码速度和质量
            playlist_path
        ]
        
        return command
    
    def _parse_ffmpeg_error(self, stderr_output: str) -> str:
        """解析 FFmpeg 错误信息"""
        network_errors = [
            'Connection refused', 'Connection timed out', '404 Not Found',
            'HTTP error', 'Failed to resolve hostname', '名称或服务未知',
            'TLS fatal alert', 'Input/output error'
        ]
        
        for error in network_errors:
            if error in stderr_output:
                return f'Network error: {error}'
        
        # 提取最后几行错误信息
        lines = stderr_output.strip().split('\n')
        if lines:
            return lines[-1]
        
        return 'Unknown error'
    
    def _start_process_monitor(self, channel_id: str, process: subprocess.Popen):
        """启动进程监控线程"""
        def monitor():
            try:
                # 监控进程状态
                process.wait()
                
                with self.lock:
                    if channel_id in self.processes:
                        process_info = self.processes[channel_id]
                        
                        if process.returncode == 0:
                            process_info.status = ProcessStatus.STOPPED
                            logger.info(f"FFmpeg process for channel {channel_id} exited normally")
                        else:
                            # 读取错误信息
                            stderr_output = process.stderr.read().decode('utf-8', errors='replace')
                            error_msg = self._parse_ffmpeg_error(stderr_output)
                            
                            process_info.status = ProcessStatus.ERROR
                            process_info.error_message = error_msg
                            logger.error(f"FFmpeg process for channel {channel_id} exited with error: {error_msg}")
                            
                            # 使用错误处理器处理错误
                            if self.error_handler:
                                self.error_handler.handle_error(
                                    channel_id=channel_id,
                                    error_message=error_msg,
                                    additional_context={
                                        'process_crashed': True,
                                        'crashed_pid': process.pid,
                                        'return_code': process.returncode,
                                        'stderr_output': stderr_output
                                    }
                                )
                        
                        # 清理资源
                        self._cleanup_process_resources(channel_id)
                        
            except Exception as e:
                logger.error(f"Error in process monitor for channel {channel_id}: {str(e)}")
        
        thread = threading.Thread(target=monitor, daemon=True, name=f"ProcessMonitor-{channel_id}")
        thread.start()
    
    def _cleanup_process_resources(self, channel_id: str):
        """清理进程相关资源"""
        try:
            # 释放并发控制锁
            self.concurrency_control.release_lock(channel_id)
            
            # 清理子进程句柄
            if channel_id in self.subprocess_handles:
                del self.subprocess_handles[channel_id]
            
            logger.debug(f"Cleaned up resources for channel {channel_id}")
            
        except Exception as e:
            logger.error(f"Error cleaning up resources for channel {channel_id}: {str(e)}")


class ProcessAlreadyRunningError(Exception):
    """进程已在运行错误"""
    pass