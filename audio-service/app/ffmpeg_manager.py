import subprocess
import threading
import time
import logging
import os
import shutil
import psutil
import fcntl
from app.config import config

logger = logging.getLogger(__name__)

class FFmpegManager:
    def __init__(self):
        self.processes = {}
        self.lock = threading.Lock()
        self.hls_threads = {}
        self.pid_lock_file = "/tmp/ffmpeg_manager.lock"
        
        # 清理现有FFmpeg进程
        self._cleanup_existing_processes()
        
        # 启动HLS清理线程
        self._start_hls_cleanup_thread()
    
    def _cleanup_existing_processes(self):
        """清理系统中现有的FFmpeg进程，防止僵尸进程积累"""
        try:
            logger.info("Cleaning up existing FFmpeg processes...")
            for proc in psutil.process_iter(['pid', 'name']):
                try:
                    if proc.info['name'] == 'ffmpeg':
                        logger.info(f"Killing existing FFmpeg process with PID {proc.info['pid']}")
                        self._terminate_process(proc)
                except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
                    continue
            logger.info("Existing FFmpeg processes cleanup completed")
        except Exception as e:
            logger.error(f"Error cleaning up existing FFmpeg processes: {str(e)}")
    
    def start_process(self, channel_id, stream_url, use_hls=False):
        # 检查进程是否已经在运行（使用文件锁确保跨进程唯一性）
        lock_file_path = f"/tmp/ffmpeg_lock_{channel_id}.lock"
        
        # 尝试获取文件锁，确保每个频道只有一个进程运行
        try:
            lock_file = open(lock_file_path, 'w')
            try:
                # 尝试获取非阻塞锁
                fcntl.flock(lock_file, fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError:
                # 锁已被其他进程持有，说明已经有进程在运行
                lock_file.close()
                logger.info(f"FFmpeg process for channel {channel_id} already running (lock held by another process)")
                # 不需要创建新进程，直接返回
                return None
        except OSError as e:
            logger.error(f"Failed to open lock file for channel {channel_id}: {str(e)}")
            raise
        
        try:
            with self.lock:
                if channel_id in self.processes:
                    if self.processes[channel_id].poll() is None:
                        logger.info(f"FFmpeg process for channel {channel_id} already running, returning existing process")
                        return self.processes[channel_id]
                    else:
                        logger.info(f"Removing terminated FFmpeg process for channel {channel_id}")
                        del self.processes[channel_id]
                
                # 检查系统中是否存在该频道的其他FFmpeg进程，如果有则清理
                self._cleanup_channel_processes(channel_id)
                
                # 创建频道专用的HLS输出目录
                channel_hls_dir = os.path.join(config.HLS_OUTPUT_DIR, str(channel_id))
                os.makedirs(channel_hls_dir, exist_ok=True)
                
                try:
                    if use_hls or config.HLS_ENABLED:
                        # 构建HLS输出的FFmpeg命令，优化稳定性
                            command = [
                                config.FFMPEG_PATH,
                                '-loglevel', 'warning',
                                '-i', stream_url,
                                '-c:a', config.FFMPEG_AUDIO_CODEC,
                                '-b:a', '128k',
                                '-f', 'hls',
                                '-hls_time', str(config.HLS_SEGMENT_DURATION),
                                '-hls_list_size', str(config.HLS_SEGMENT_LIST_SIZE),
                                '-hls_segment_filename', os.path.join(channel_hls_dir, 'segment_%03d.ts'),
                                '-hls_flags', 'delete_segments+program_date_time+temp_file',
                                '-hls_delete_threshold', '2',
                                '-hls_allow_cache', '1',
                                '-hls_playlist_type', 'event',
                                '-copyts',
                                '-fflags', '+discardcorrupt+igndts+genpts',
                                '-max_muxing_queue_size', '2048',
                                '-user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                                '-avoid_negative_ts', 'make_zero',
                                '-reconnect', '1',
                                '-reconnect_streamed', '1',
                                '-reconnect_delay_max', '15',
                                '-timeout', '30',
                                '-probesize', '8000000',
                                '-analyzeduration', '10000000',
                                '-bufsize', '384k',
                                '-apad',
                                '-maxrate', '160k',
                                '-minrate', '96k',
                                '-flags', '+global_header',
                                '-mpegts_flags', 'initial_discontinuity',
                                # 添加HTTP相关选项，处理301/302跳转
                                '-http_persistent', '1',
                                '-follow_redirects', '1',
                                '-max_redirections', '10',
                                '-http_keepalive', '1',
                                '-headers', 'Accept: */*\r\n',
                                os.path.join(channel_hls_dir, 'playlist.m3u8')
                            ]
                    else:
                        # 构建MP3输出的FFmpeg命令
                        command = [
                            config.FFMPEG_PATH,
                            '-i', stream_url,
                            '-c:a', config.FFMPEG_AUDIO_CODEC,
                            '-b:a', '128k',
                            '-f', 'mp3',
                            '-preset', config.FFMPEG_PRESET,
                            '-tune', config.FFMPEG_TUNE,
                            '-'
                        ]
                    
                    logger.info(f'Starting FFmpeg process with command: {" ".join(command)}')
                    
                    # 启动进程，并立即检查是否有错误输出
                    process = subprocess.Popen(
                        command,
                        stdout=subprocess.DEVNULL if (use_hls or config.HLS_ENABLED) else subprocess.PIPE,
                        stderr=subprocess.PIPE,
                        bufsize=1024*1024
                    )
                    
                    # 检查进程是否立即终止（通常是因为网络问题或无效URL）
                    time.sleep(2)  # 等待2秒，让FFmpeg有时间处理输入
                    
                    if process.poll() is not None:
                        # 进程已经终止，读取错误信息
                        stderr_output = process.stderr.read().decode('utf-8', errors='replace')
                        logger.error(f'FFmpeg process exited immediately with code {process.returncode}: {stderr_output}')
                        
                        # 检查常见的网络错误
                        network_errors = ['Connection refused', 'Connection timed out', '404 Not Found', 'HTTP error', 'Failed to resolve hostname', '名称或服务未知', 'TLS fatal alert', 'Input/output error']
                        for error in network_errors:
                            if error in stderr_output:
                                raise Exception(f'Network error: {error}')
                        
                        raise Exception(f'FFmpeg failed with code {process.returncode}: {stderr_output}')
                    
                    # 将进程添加到字典中
                    self.processes[channel_id] = process
                    logger.info(f'Started FFmpeg process for channel {channel_id}, use_hls={use_hls}, PID: {process.pid}')
                    
                    # 启动错误监控线程
                    threading.Thread(
                        target=self._monitor_errors,
                        args=(channel_id, process.stderr),
                        daemon=True
                    ).start()
                    
                    # 启动锁释放监控线程，当进程结束时释放锁
                    def monitor_process_and_release_lock(process, lock_file, lock_file_path):
                        try:
                            process.wait()
                        finally:
                            try:
                                fcntl.flock(lock_file, fcntl.LOCK_UN)
                                lock_file.close()
                                os.remove(lock_file_path)
                            except OSError:
                                pass
                            logger.info(f'FFmpeg process {process.pid} for channel {channel_id} finished, lock released')
                    
                    threading.Thread(
                        target=monitor_process_and_release_lock,
                        args=(process, lock_file, lock_file_path),
                        daemon=True
                    ).start()
                    
                    return process
                except (OSError, subprocess.SubprocessError) as e:
                    logger.error(f'Failed to start FFmpeg process for channel {channel_id}: {str(e)}')
                    # 释放锁
                    try:
                        fcntl.flock(lock_file, fcntl.LOCK_UN)
                        lock_file.close()
                        os.remove(lock_file_path)
                    except OSError:
                        pass
                    raise
        except (OSError, subprocess.SubprocessError) as e:
            logger.error(f'Error in start_process for channel {channel_id}: {str(e)}')
            # 确保锁被释放
            try:
                fcntl.flock(lock_file, fcntl.LOCK_UN)
                lock_file.close()
                os.remove(lock_file_path)
            except OSError:
                pass
            raise
    
    def _cleanup_channel_processes(self, channel_id):
        """清理系统中指定频道的FFmpeg进程"""
        try:
            logger.info(f"Cleaning up FFmpeg processes for channel {channel_id}...")
            channel_hls_dir = os.path.join(config.HLS_OUTPUT_DIR, str(channel_id))
            found_process = False
            
            for proc in psutil.process_iter(['pid', 'name']):
                try:
                    if proc.info['name'] == 'ffmpeg':
                        try:
                            cmdline = proc.cmdline()
                            cmdline_str = ' '.join(cmdline)
                            if channel_hls_dir in cmdline_str:
                                found_process = True
                                logger.info(f"Found existing FFmpeg process for channel {channel_id} with PID {proc.info['pid']}")
                                self._terminate_process(proc)
                                logger.info(f"Successfully terminated FFmpeg process for channel {channel_id} with PID {proc.info['pid']}")
                        except (psutil.NoSuchProcess, psutil.AccessDenied):
                            continue
                except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
                    continue
            
            if not found_process:
                logger.info(f"No existing FFmpeg processes found for channel {channel_id}")
            
            logger.info(f"FFmpeg processes cleanup for channel {channel_id} completed")
        except Exception as e:
            logger.error(f"Error cleaning up FFmpeg processes for channel {channel_id}: {str(e)}")
    
    def _start_hls_cleanup_thread(self):
        """启动HLS切片清理线程"""
        def cleanup():    
            while True:
                try:
                    self._cleanup_hls_segments()
                except Exception as e:
                    logger.error(f'HLS cleanup error: {str(e)}')
                time.sleep(config.HLS_CLEANUP_INTERVAL)
        
        thread = threading.Thread(target=cleanup, daemon=True)
        thread.start()
    
    def _cleanup_hls_segments(self):
        """清理过期的HLS切片，并统一文件名格式"""
        current_time = time.time()
        expected_prefix = config.HLS_SEGMENT_PREFIX
        playlist_name = config.HLS_PLAYLIST_NAME
        
        # 遍历所有频道的HLS目录
        try:
            for channel_id in os.listdir(config.HLS_OUTPUT_DIR):
                channel_dir = os.path.join(config.HLS_OUTPUT_DIR, channel_id)
                if not os.path.isdir(channel_dir):
                    continue
                
                # 遍历所有文件
                try:
                    for filename in os.listdir(channel_dir):
                        file_path = os.path.join(channel_dir, filename)
                        try:
                            file_mtime = os.path.getmtime(file_path)
                            
                            # 检查文件是否为有效类型
                            if filename == playlist_name:
                                # 保留播放列表文件
                                continue
                            elif not filename.startswith(expected_prefix) or not filename.endswith('.ts'):
                                # 删除非预期格式的文件（如旧的playlist*.ts文件、临时文件等）
                                os.remove(file_path)
                                logger.debug(f'Deleted invalid HLS file: {file_path}')
                            elif current_time - file_mtime > config.HLS_MAX_AGE:
                                # 删除过期文件
                                os.remove(file_path)
                                logger.debug(f'Deleted expired HLS file: {file_path}')
                        except OSError as e:
                            logger.error(f'Failed to process HLS file {file_path}: {str(e)}')
                except OSError as e:
                    logger.error(f'Failed to list HLS directory {channel_dir}: {str(e)}')
                
                # 检查目录是否为空，为空则删除
                try:
                    if not os.listdir(channel_dir):
                        os.rmdir(channel_dir)
                        logger.debug(f'Deleted empty HLS directory: {channel_dir}')
                except OSError as e:
                    logger.error(f'Failed to delete HLS directory {channel_dir}: {str(e)}')
        except OSError as e:
            logger.error(f'Failed to list HLS output directory: {str(e)}')
    
    def _terminate_process(self, process):
        """优雅地终止进程"""
        try:
            process.terminate()
            process.wait(timeout=3)
        except (psutil.TimeoutExpired, subprocess.TimeoutExpired):
            try:
                process.kill()
            except (psutil.NoSuchProcess, psutil.AccessDenied):
                pass
    
    def stop_process(self, channel_id):
        with self.lock:
            if channel_id not in self.processes:
                return False
            
            process = self.processes[channel_id]
            self._terminate_process(process)
            del self.processes[channel_id]
        
        logger.info(f'Stopped FFmpeg process for channel {channel_id}')
        return True
    
    def is_running(self, channel_id):
        # 首先检查当前worker的进程字典
        with self.lock:
            if channel_id in self.processes:
                if self.processes[channel_id].poll() is None:
                    return True
                else:
                    # 进程已终止，从字典中移除
                    del self.processes[channel_id]
        
        # 然后检查系统中是否存在该频道的其他FFmpeg进程
        channel_hls_dir = os.path.join(config.HLS_OUTPUT_DIR, str(channel_id))
        for proc in psutil.process_iter(['pid', 'name']):
            try:
                if proc.info['name'] == 'ffmpeg':
                    try:
                        cmdline_str = ' '.join(proc.cmdline())
                        if channel_hls_dir in cmdline_str and proc.status() != 'zombie':
                            return True
                    except (psutil.NoSuchProcess, psutil.AccessDenied):
                        continue
            except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
                continue
        
        # 最后检查锁文件是否存在，如果存在，说明其他进程正在处理该频道
        lock_file_path = f"/tmp/ffmpeg_lock_{channel_id}.lock"
        if os.path.exists(lock_file_path):
            # 检查锁文件是否被其他进程持有
            try:
                with open(lock_file_path, 'w') as lock_file:
                    fcntl.flock(lock_file, fcntl.LOCK_EX | fcntl.LOCK_NB)
                    # 如果能获取锁，说明锁文件是残留的，删除它
                    os.remove(lock_file_path)
                    return False
            except BlockingIOError:
                # 锁被其他进程持有，说明该频道正在被处理
                return True
            except OSError:
                # 其他错误，删除锁文件
                try:
                    os.remove(lock_file_path)
                except OSError:
                    pass
                return False
        
        return False
    
    def _monitor_errors(self, channel_id, stderr):
        critical_errors = [
            b'Connection refused',
            b'Connection timed out',
            b'404 Not Found',
            b'HTTP error 4',
            b'Input/output error',
            b'Could not write to output file',
            b'Output file #0 does not contain any stream',
            b'Invalid data found when processing input',
            b'Error while decoding stream',
            b'Error while filtering stream'
        ]
        
        while True:
            try:
                line = stderr.readline()
                if not line:
                    break
                
                logger.debug(f'FFmpeg Error [{channel_id}]: {line.decode("utf-8", errors="replace")}')
                
                # 检查关键错误
                for error in critical_errors:
                    if error in line:
                        logger.warning(f'Critical FFmpeg error detected in stderr, restarting process for channel {channel_id}')
                        break
            except (OSError, ValueError):
                break

ffmpeg_manager = FFmpegManager()
