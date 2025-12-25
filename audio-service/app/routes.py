"""
Flask 路由定义

实现音频处理服务的 REST API 接口。
"""

import logging
import os
import time
from flask import request, jsonify, send_file, Response
from datetime import datetime

from app import app
from app.audio_service import get_audio_service, initialize_service
from app.process_manager import ProcessAlreadyRunningError

logger = logging.getLogger(__name__)

# 初始化服务
audio_service = None

def init_service():
    """初始化服务"""
    global audio_service
    try:
        audio_service = initialize_service()
        logger.info("Audio service initialized successfully")
    except Exception as e:
        logger.error(f"Failed to initialize audio service: {str(e)}")
        raise


def get_service():
    """获取音频服务实例"""
    global audio_service
    if audio_service is None:
        # 确保服务已初始化
        from app import ensure_services_initialized
        if ensure_services_initialized():
            audio_service = get_audio_service()
        else:
            # 如果初始化失败，尝试直接初始化
            init_service()
    return audio_service


@app.route('/api/process/<channel_id>/start', methods=['POST'])
def start_process(channel_id):
    """启动 FFmpeg 进程"""
    try:
        # 获取请求参数
        data = request.get_json() or {}
        stream_url = data.get('stream_url') or request.args.get('stream_url')
        
        if not stream_url:
            return jsonify({
                'code': 400,
                'message': 'Missing required parameter: stream_url'
            }), 400
        
        # 启动进程
        service = get_service()
        process_info = service.process_manager.start_process(channel_id, stream_url)
        
        logger.info(f"Started process for channel {channel_id}, PID: {process_info.pid}")
        
        return jsonify({
            'code': 200,
            'message': 'Process started successfully',
            'data': {
                'channel_id': process_info.channel_id,
                'pid': process_info.pid,
                'status': process_info.status.value,
                'start_time': process_info.start_time.isoformat(),
                'hls_output_dir': process_info.hls_output_dir
            }
        })
    
    except ProcessAlreadyRunningError as e:
        logger.warning(f"Process already running for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 409,
            'message': str(e)
        }), 409
    
    except ValueError as e:
        logger.error(f"Invalid parameters for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 400,
            'message': str(e)
        }), 400
    
    except Exception as e:
        logger.error(f"Failed to start process for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to start process: {str(e)}'
        }), 500


@app.route('/api/process/<channel_id>/stop', methods=['POST'])
def stop_process(channel_id):
    """停止 FFmpeg 进程"""
    try:
        service = get_service()
        success = service.process_manager.stop_process(channel_id)
        
        if success:
            logger.info(f"Stopped process for channel {channel_id}")
            return jsonify({
                'code': 200,
                'message': 'Process stopped successfully',
                'data': {
                    'channel_id': channel_id,
                    'status': 'stopped'
                }
            })
        else:
            logger.warning(f"Process not found for channel {channel_id}")
            return jsonify({
                'code': 404,
                'message': 'Process not found'
            }), 404
    
    except Exception as e:
        logger.error(f"Failed to stop process for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to stop process: {str(e)}'
        }), 500


@app.route('/api/process/<channel_id>/status', methods=['GET'])
def get_process_status(channel_id):
    """获取进程状态"""
    try:
        service = get_service()
        process_info = service.process_manager.get_process_status(channel_id)
        
        if process_info is None:
            return jsonify({
                'code': 404,
                'message': 'Process not found'
            }), 404
        
        return jsonify({
            'code': 200,
            'message': 'success',
            'data': {
                'channel_id': process_info.channel_id,
                'pid': process_info.pid,
                'status': process_info.status.value,
                'stream_url': process_info.stream_url,
                'start_time': process_info.start_time.isoformat(),
                'last_activity_time': process_info.last_activity_time.isoformat(),
                'error_message': process_info.error_message,
                'hls_output_dir': process_info.hls_output_dir
            }
        })
    
    except Exception as e:
        logger.error(f"Failed to get status for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to get process status: {str(e)}'
        }), 500


@app.route('/api/processes', methods=['GET'])
def list_processes():
    """列出所有进程"""
    try:
        service = get_service()
        processes = service.process_manager.list_processes()
        
        process_list = []
        for process_info in processes:
            process_data = {
                'channel_id': process_info.channel_id,
                'pid': process_info.pid,
                'status': process_info.status.value,
                'start_time': process_info.start_time.isoformat(),
                'last_activity_time': process_info.last_activity_time.isoformat()
            }
            
            if process_info.error_message:
                process_data['error_message'] = process_info.error_message
            
            process_list.append(process_data)
        
        return jsonify({
            'code': 200,
            'message': 'success',
            'data': {
                'total': len(process_list),
                'processes': process_list
            }
        })
    
    except Exception as e:
        logger.error(f"Failed to list processes: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to list processes: {str(e)}'
        }), 500


@app.route('/api/process/<channel_id>/logs', methods=['GET'])
def get_process_logs(channel_id):
    """获取进程日志"""
    try:
        lines = request.args.get('lines', 100, type=int)
        
        # TODO: 实现日志读取功能
        # 这里暂时返回占位符响应
        return jsonify({
            'code': 200,
            'message': 'success',
            'data': {
                'channel_id': channel_id,
                'logs': [
                    f"[{datetime.now().isoformat()}] Log functionality not yet implemented",
                    f"[{datetime.now().isoformat()}] Requested {lines} lines for channel {channel_id}"
                ]
            }
        })
    
    except Exception as e:
        logger.error(f"Failed to get logs for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to get process logs: {str(e)}'
        }), 500


@app.route('/api/process/<channel_id>/activity', methods=['POST'])
def update_activity(channel_id):
    """更新进程活动时间"""
    try:
        service = get_service()
        service.process_manager.update_activity_time(channel_id)
        
        return jsonify({
            'code': 200,
            'message': 'Activity time updated',
            'data': {
                'channel_id': channel_id,
                'updated_at': datetime.now().isoformat()
            }
        })
    
    except Exception as e:
        logger.error(f"Failed to update activity for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to update activity: {str(e)}'
        }), 500


@app.route('/api/status', methods=['GET'])
def service_status():
    """获取服务状态"""
    try:
        service = get_service()
        status = service.get_status()
        
        return jsonify({
            'code': 200,
            'message': 'success',
            'data': status
        })
    
    except Exception as e:
        logger.error(f"Failed to get service status: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to get service status: {str(e)}'
        }), 500


@app.route('/api/info', methods=['GET'])
def application_info():
    """获取应用信息"""
    try:
        from app.config import config
        import sys
        import platform
        
        return jsonify({
            'code': 200,
            'message': 'success',
            'data': {
                'application': {
                    'name': 'Audio Processing Service',
                    'version': '2.0.0',
                    'description': '重构后的音频处理服务，提供 FFmpeg 进程管理和 HLS 流处理'
                },
                'system': {
                    'python_version': sys.version,
                    'platform': platform.platform(),
                    'architecture': platform.architecture()[0]
                },
                'configuration': {
                    'host': config.HOST,
                    'port': config.PORT,
                    'debug': config.DEBUG,
                    'hls_output_dir': config.HLS_OUTPUT_DIR,
                    'idle_timeout': config.IDLE_TIMEOUT,
                    'cleanup_interval': config.CLEANUP_INTERVAL
                }
            }
        })
    
    except Exception as e:
        logger.error(f"Failed to get application info: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to get application info: {str(e)}'
        }), 500


@app.route('/health', methods=['GET'])
def health_check_simple():
    """简单健康检查端点"""
    try:
        service = get_service()
        status = service.get_status()
        
        # 简单的健康状态判断
        is_healthy = (
            status.get('initialized', False) and 
            status.get('running', False) and
            status.get('system_health', {}).get('overall_status') != 'error'
        )
        
        if is_healthy:
            return jsonify({
                'status': 'healthy',
                'timestamp': datetime.now().isoformat()
            }), 200
        else:
            return jsonify({
                'status': 'unhealthy',
                'timestamp': datetime.now().isoformat(),
                'details': status
            }), 503
    
    except Exception as e:
        logger.error(f"Health check failed: {str(e)}")
        return jsonify({
            'status': 'error',
            'message': str(e),
            'timestamp': datetime.now().isoformat()
        }), 503


@app.route('/api/health', methods=['GET'])
def health_check():
    """系统健康检查"""
    try:
        service = get_service()
        health_status = service.error_handler.check_system_health()
        
        # 根据健康状态设置HTTP状态码
        status_code = 200
        if health_status['overall_status'] == 'warning':
            status_code = 200  # 警告仍然返回200，但在响应中标明
        elif health_status['overall_status'] == 'error':
            status_code = 503  # 服务不可用
        
        return jsonify({
            'code': status_code,
            'message': f"System status: {health_status['overall_status']}",
            'data': health_status
        }), status_code
    
    except Exception as e:
        logger.error(f"Failed to perform health check: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Health check failed: {str(e)}'
        }), 500


@app.route('/api/errors', methods=['GET'])
def get_error_history():
    """获取错误历史"""
    try:
        minutes = request.args.get('minutes', 60, type=int)
        service = get_service()
        
        recent_errors = service.error_handler.get_recent_errors(minutes)
        error_stats = service.error_handler.get_error_statistics()
        
        error_list = []
        for error in recent_errors:
            error_data = {
                'error_type': error.error_type.value,
                'channel_id': error.channel_id,
                'error_message': error.error_message,
                'timestamp': error.timestamp.isoformat(),
                'recovery_attempted': error.recovery_attempted,
                'recovery_successful': error.recovery_successful
            }
            
            if error.additional_info:
                error_data['additional_info'] = error.additional_info
            
            error_list.append(error_data)
        
        return jsonify({
            'code': 200,
            'message': 'success',
            'data': {
                'recent_errors': error_list,
                'statistics': error_stats,
                'time_range_minutes': minutes
            }
        })
    
    except Exception as e:
        logger.error(f"Failed to get error history: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to get error history: {str(e)}'
        }), 500


@app.route('/api/recovery/<channel_id>', methods=['POST'])
def trigger_recovery(channel_id):
    """手动触发错误恢复"""
    try:
        service = get_service()
        
        # 检查是否有该频道的错误记录
        recent_errors = service.error_handler.get_recent_errors(30)  # 最近30分钟
        channel_errors = [e for e in recent_errors if e.channel_id == channel_id]
        
        if not channel_errors:
            return jsonify({
                'code': 404,
                'message': f'No recent errors found for channel {channel_id}'
            }), 404
        
        # 获取最近的错误
        latest_error = max(channel_errors, key=lambda e: e.timestamp)
        
        # 重新尝试恢复
        service.error_handler._attempt_recovery(latest_error)
        
        return jsonify({
            'code': 200,
            'message': 'Recovery attempt completed',
            'data': {
                'channel_id': channel_id,
                'error_type': latest_error.error_type.value,
                'recovery_successful': latest_error.recovery_successful,
                'timestamp': datetime.now().isoformat()
            }
        })
    
    except Exception as e:
        logger.error(f"Failed to trigger recovery for channel {channel_id}: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to trigger recovery: {str(e)}'
        }), 500


@app.route('/api/cleanup', methods=['POST'])
def force_cleanup():
    """强制执行资源清理"""
    try:
        service = get_service()
        result = service.resource_cleaner.force_cleanup()
        
        if result['success']:
            return jsonify({
                'code': 200,
                'message': result['message']
            })
        else:
            return jsonify({
                'code': 500,
                'message': result['message']
            }), 500
    
    except Exception as e:
        logger.error(f"Failed to perform cleanup: {str(e)}")
        return jsonify({
            'code': 500,
            'message': f'Failed to perform cleanup: {str(e)}'
        }), 500


@app.route('/hls/<channel_id>/<filename>', methods=['GET', 'OPTIONS'])
def serve_hls_file(channel_id, filename):
    """提供 HLS 播放列表和切片文件"""
    
    # 处理 CORS 预检请求
    if request.method == 'OPTIONS':
        response = Response()
        response.headers['Access-Control-Allow-Origin'] = '*'
        response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
        response.headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization'
        response.headers['Access-Control-Max-Age'] = '3600'
        return response
    
    try:
        # 验证文件名格式，防止路径遍历
        if '..' in filename or '/' in filename:
            return jsonify({
                'code': 400,
                'message': 'Invalid file name'
            }), 400
        
        # 获取服务实例
        service = get_service()
        
        # 构建文件路径
        from app.config import config
        file_path = os.path.join(config.HLS_OUTPUT_DIR, channel_id, filename)
        
        # 对于播放列表文件，等待其生成
        if filename.endswith('.m3u8'):
            max_wait_time = 0.5  # 减少到0.5秒
            wait_interval = 0.02  # 更频繁的检查，每20ms
            waited_time = 0
            
            while not os.path.exists(file_path) and waited_time < max_wait_time:
                time.sleep(wait_interval)
                waited_time += wait_interval
                
                # 检查进程是否还在运行
                process_status = service.process_manager.get_process_status(channel_id)
                if not process_status or process_status.status.value != 'running':
                    break
        
        # 检查文件是否存在
        if not os.path.exists(file_path):
            logger.debug(f'HLS file not found: {file_path}')
            return jsonify({
                'code': 404,
                'message': 'HLS file not found'
            }), 404
        
        # 设置 CORS 头
        def add_cors_headers(response):
            response.headers['Access-Control-Allow-Origin'] = '*'
            response.headers['Access-Control-Allow-Methods'] = 'GET, OPTIONS'
            response.headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization'
            response.headers['Access-Control-Max-Age'] = '3600'
            return response
        
        # 返回文件
        if filename.endswith('.m3u8'):
            response = send_file(
                file_path,
                mimetype='application/vnd.apple.mpegurl',
                as_attachment=False
            )
            response.headers['Cache-Control'] = 'no-cache, no-store, must-revalidate'
            return add_cors_headers(response)
        elif filename.endswith('.ts'):
            response = send_file(
                file_path,
                mimetype='video/MP2T',
                as_attachment=False
            )
            response.headers['Cache-Control'] = 'public, max-age=60'
            return add_cors_headers(response)
        else:
            return jsonify({
                'code': 400,
                'message': 'Unsupported file type'
            }), 400
            
    except Exception as e:
        logger.error(f'Error serving HLS file {channel_id}/{filename}: {str(e)}')
        return jsonify({
            'code': 500,
            'message': f'Failed to serve HLS file: {str(e)}'
        }), 500


@app.route('/stream/<channel_id>', methods=['GET'])
def serve_audio_stream(channel_id):
    """提供音频流（MP3格式）"""
    try:
        service = get_service()
        
        # 检查进程状态
        process_status = service.process_manager.get_process_status(channel_id)
        if not process_status or process_status.status.value != 'running':
            return jsonify({
                'code': 404,
                'message': 'Audio stream not available'
            }), 404
        
        # TODO: 实现MP3流代理
        # 这里暂时返回重定向到HLS
        return jsonify({
            'code': 302,
            'message': 'Redirecting to HLS stream',
            'hls_url': f'/hls/{channel_id}/playlist.m3u8'
        }), 302
        
    except Exception as e:
        logger.error(f'Error serving audio stream for channel {channel_id}: {str(e)}')
        return jsonify({
            'code': 500,
            'message': f'Failed to serve audio stream: {str(e)}'
        }), 500


@app.errorhandler(404)
def not_found(error):
    """404 错误处理"""
    return jsonify({
        'code': 404,
        'message': 'Endpoint not found'
    }), 404


@app.errorhandler(405)
def method_not_allowed(error):
    """405 错误处理"""
    return jsonify({
        'code': 405,
        'message': 'Method not allowed'
    }), 405


@app.errorhandler(500)
def internal_error(error):
    """500 错误处理"""
    logger.error(f"Internal server error: {str(error)}")
    return jsonify({
        'code': 500,
        'message': 'Internal server error'
    }), 500