import logging
import time
from app.ffmpeg_manager import ffmpeg_manager
from app.config import config

logger = logging.getLogger(__name__)

def handle_stream(channel_id, stream_url, output_callback, use_hls=False):
    """处理音频流，将FFmpeg输出传递给回调函数"""
    # 启动FFmpeg进程
    process = ffmpeg_manager.start_process(channel_id, stream_url, use_hls)
    
    # 如果使用HLS模式，不需要读取stdout，但需要持续监控
    if use_hls or config.HLS_ENABLED:
        retry_count = 0
        max_retries = 5
        retry_delay = 2  # 初始重试延迟2秒
        
        try:
            # 持续监控进程，确保HLS切片持续生成
            while True:
                # 检查进程是否在运行
                if ffmpeg_manager.is_running(channel_id):
                    # 进程正常运行，重置重试计数和延迟
                    retry_count = 0
                    retry_delay = 2
                    time.sleep(1)  # 每秒检查一次
                else:
                    # 进程已停止，尝试重启
                    retry_count += 1
                    if retry_count > max_retries:
                        logger.error(f'Failed to restart FFmpeg process for channel {channel_id} after {max_retries} attempts')
                        break
                    
                    logger.warning(f'FFmpeg process for channel {channel_id} stopped, restarting ({retry_count}/{max_retries})...')
                    try:
                        ffmpeg_manager.start_process(channel_id, stream_url, use_hls)
                        # 指数退避策略，避免频繁重启
                        time.sleep(retry_delay)
                        retry_delay = min(retry_delay * 2, 30)  # 最大延迟30秒
                    except Exception as e:
                        logger.error(f'Failed to restart FFmpeg process for channel {channel_id}: {str(e)}')
                        time.sleep(retry_delay)
                        retry_delay = min(retry_delay * 2, 30)
        except Exception as e:
            logger.error(f'Error handling HLS stream for channel {channel_id}: {str(e)}')
            # 异常情况下也要尝试重启
            try:
                logger.info(f'Restarting FFmpeg process after exception for channel {channel_id}')
                ffmpeg_manager.start_process(channel_id, stream_url, use_hls)
            except Exception as restart_e:
                logger.error(f'Failed to restart FFmpeg process after exception: {str(restart_e)}')
    else:
        # 传统MP3流模式
        try:
            last_success_time = time.time()
            cache = b''
            
            while ffmpeg_manager.is_running(channel_id):
                # 读取音频数据
                data = process.stdout.read(8192)  # 8KB chunks
                if not data:
                    # 检查超时
                    if time.time() - last_success_time > config.MAX_SILENT_TIME:
                        logger.warning(f'No output from FFmpeg for channel {channel_id}, restarting')
                        break
                    time.sleep(0.01)
                    continue
                
                # 更新最后成功时间
                last_success_time = time.time()
                
                # 输出音频数据
                output_callback(data)
                
                # 缓存数据
                if len(cache) < config.CACHE_SIZE:
                    cache += data
            
        except Exception as e:
            logger.error(f'Error handling stream for channel {channel_id}: {str(e)}')
        finally:
            # 检查进程是否还在运行，不在则重启
            if not ffmpeg_manager.is_running(channel_id):
                logger.info(f'Restarting FFmpeg process for channel {channel_id}')
                ffmpeg_manager.start_process(channel_id, stream_url, use_hls)
