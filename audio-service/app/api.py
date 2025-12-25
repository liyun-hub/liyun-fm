from flask import request, Response, jsonify, send_file
from app import app
from app.ffmpeg_manager import ffmpeg_manager
from app.stream_handler import handle_stream
from app.config import config
import logging
import threading
import time
import os

logger = logging.getLogger(__name__)

@app.route('/api/stream', methods=['GET'])
def stream():
    """获取转码后的音频流（支持MP3和HLS模式）"""
    try:
        channel_id = request.args.get('channel_id')
        stream_url = request.args.get('stream_url')
        use_hls = request.args.get('use_hls', 'true').lower() == 'true'
        
        if not channel_id or not stream_url:
            logger.error('Missing required parameters')
            return Response('Missing required parameters', status=400)
        
        logger.info(f'Stream request: channel={channel_id}, use_hls={use_hls}')
        
        # 默认使用HLS模式，重定向到HLS播放列表
        if use_hls:
            return jsonify({
                'hls_playlist_url': f'/api/hls/{channel_id}/{config.HLS_PLAYLIST_NAME}?stream_url={stream_url}',
                'channel_id': channel_id,
                'stream_url': stream_url
            })
        
        logger.info(f'Received MP3 stream request for channel {channel_id}')
        
        # 音频数据缓冲区
        audio_buffer = []
        buffer_lock = threading.Lock()
        
        def output_callback(data):
            """将音频数据添加到缓冲区"""
            with buffer_lock:
                audio_buffer.append(data)
        
        def generate():
            """启动流处理线程"""
            handle_stream(channel_id, stream_url, output_callback, use_hls=False)
        
        # 启动流处理线程
        threading.Thread(target=generate, daemon=True).start()
        
        def stream_generator():
            """生成器函数，从缓冲区读取数据并返回给客户端"""
            while True:
                # 检查是否有数据
                with buffer_lock:
                    if audio_buffer:
                        # 获取并移除第一个数据块
                        data = audio_buffer.pop(0)
                        yield data
                
                # 检查进程是否还在运行
                if not ffmpeg_manager.is_running(channel_id):
                    # 等待缓冲区清空
                    time.sleep(0.1)
                    with buffer_lock:
                        if not audio_buffer:
                            break
                
                # 短暂休眠，避免CPU占用过高
                time.sleep(0.001)
        
        return Response(stream_generator(), mimetype='audio/mpeg')
    except Exception as e:
        logger.error(f'Stream error: {str(e)}', exc_info=True)
        return jsonify({'error': str(e)}), 500

@app.route('/api/hls/<channel_id>/<playlist_name>', methods=['GET'])
def hls_playlist(channel_id, playlist_name):
    """获取HLS播放列表或切片文件"""
    try:
        # 验证文件名格式，防止路径遍历
        if '..' in playlist_name or '/' in playlist_name:
            return Response('Invalid file name', status=400)
        
        # 启动FFmpeg进程（如果还没有启动）
        stream_url = request.args.get('stream_url')
        if stream_url:
            # 如果提供了stream_url，确保FFmpeg进程正在运行
            if not ffmpeg_manager.is_running(channel_id):
                logger.info(f'Starting HLS stream for channel {channel_id}')
                def dummy_callback(data):
                    pass
                threading.Thread(
                    target=handle_stream,
                    args=(channel_id, stream_url, dummy_callback, True),
                    daemon=True
                ).start()
        
        # 构建文件路径
        file_path = os.path.join(config.HLS_OUTPUT_DIR, channel_id, playlist_name)
        
        # 等待文件生成（仅对播放列表）
        if playlist_name.endswith('.m3u8'):
            max_wait_time = 30
            wait_interval = 0.5
            waited_time = 0
            
            while not os.path.exists(file_path) and waited_time < max_wait_time:
                time.sleep(wait_interval)
                waited_time += wait_interval
        
        # 检查文件是否存在
        if not os.path.exists(file_path):
            logger.debug(f'HLS file not found: {file_path}')
            return Response('HLS file not found', status=404)
        
        # 返回文件
        if playlist_name.endswith('.m3u8'):
            return send_file(
                file_path,
                mimetype='application/vnd.apple.mpegurl',
                as_attachment=False
            )
        else:
            return send_file(
                file_path,
                mimetype='video/MP2T',
                as_attachment=False
            )
    except Exception as e:
        logger.error(f'HLS error for {channel_id}/{playlist_name}: {str(e)}', exc_info=True)
        return jsonify({'error': str(e)}), 500

@app.route('/api/status', methods=['GET'])
def status():
    """获取服务状态"""
    return jsonify({
        'status': 'running',
        'active_processes': len(ffmpeg_manager.processes)
    })

@app.route('/api/processes', methods=['GET'])
def get_processes():
    """获取活跃进程列表"""
    return jsonify({
        'processes': list(ffmpeg_manager.processes.keys())
    })

@app.route('/api/process/<channel_id>', methods=['POST'])
def start_process(channel_id):
    """启动指定频道的FFmpeg进程"""
    stream_url = request.args.get('stream_url')
    if not stream_url:
        return jsonify({'error': 'Missing stream_url parameter'}), 400
    
    try:
        ffmpeg_manager.start_process(channel_id, stream_url)
        return jsonify({'status': 'started'})
    except Exception as e:
        error_msg = str(e)
        logger.error(f'Failed to start process for channel {channel_id}: {error_msg}')
        
        # 检查是否是网络错误
        if 'Network error' in error_msg:
            return jsonify({'error': f'Network error: {error_msg}', 'type': 'network'}), 400
        
        return jsonify({'error': error_msg}), 500

@app.route('/api/process/<channel_id>', methods=['DELETE'])
def stop_process(channel_id):
    """停止指定频道的FFmpeg进程"""
    result = ffmpeg_manager.stop_process(channel_id)
    return jsonify({'status': 'stopped' if result else 'not_found'})

@app.errorhandler(500)
def handle_500(error):
    logger.error(f'500 Error: {str(error)}', exc_info=True)
    return jsonify({'code': 500, 'message': '服务器错误'}), 500

@app.errorhandler(404)
def handle_404(error):
    logger.warning(f'404 Error: {request.path}')
    return jsonify({'code': 404, 'message': '资源不存在'}), 404
