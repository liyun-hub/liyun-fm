import os
import yaml
import logging
from pathlib import Path
from typing import Dict, Any, Optional

logger = logging.getLogger(__name__)


class AudioServiceConfig:
    """音频服务配置类"""
    
    def __init__(self, config_file: Optional[str] = None):
        # 默认配置
        self._defaults = {
            # 服务配置
            'service': {
                'host': '0.0.0.0',
                'port': 5000,
                'debug': False,
                'log_level': 'INFO'
            },
            
            # FFmpeg 配置
            'ffmpeg': {
                'path': '/usr/bin/ffmpeg',
                'audio_codec': 'aac',
                'bitrate': '128k',
                'preset': 'fast',
                'tune': 'audio'
            },
            
            # HLS 配置
            'hls': {
                'output_dir': '/tmp/hls',
                'playlist_name': 'playlist.m3u8',
                'segment_duration': 6,
                'segment_list_size': 35,
                'segment_prefix': 'segment_',
                'max_age': 720,
                'cleanup_interval': 180
            },
            
            # 并发控制配置
            'concurrency': {
                'lock_dir': '/tmp',
                'lock_timeout': 30
            },
            
            # 空闲进程配置
            'idle_process': {
                'timeout': 300,  # 5 分钟
                'check_interval': 60  # 1 分钟
            },
            
            # 资源清理配置
            'cleanup': {
                'interval': 180,  # 3 分钟
                'max_log_size': 10485760  # 10 MB
            },
            
            # 错误处理配置
            'error_handling': {
                'min_free_space_mb': 500,  # 最小空闲磁盘空间 (MB)
                'max_error_history': 1000,  # 最大错误历史记录数
                'disk_check_interval': 300,  # 磁盘检查间隔 (秒)
                'auto_recovery_enabled': True,  # 是否启用自动恢复
                'network_retry_delay': 30,  # 网络错误重试延迟 (秒)
                'max_recovery_attempts': 3  # 最大恢复尝试次数
            }
        }
        
        # 加载配置
        self._config = self._load_config(config_file)
        
        # 验证配置
        self._validate_config()
        
        # 创建必要的目录
        self._create_directories()
    
    def _load_config(self, config_file: Optional[str]) -> Dict[str, Any]:
        """加载配置文件"""
        config = self._defaults.copy()
        
        # 尝试加载配置文件
        if config_file:
            config_path = Path(config_file)
        else:
            # 默认配置文件位置
            config_path = Path('/etc/audio-service/config.yaml')
            if not config_path.exists():
                config_path = Path('config.yaml')
        
        if config_path.exists():
            try:
                with open(config_path, 'r', encoding='utf-8') as f:
                    file_config = yaml.safe_load(f) or {}
                
                # 深度合并配置
                config = self._deep_merge(config, file_config)
                logger.info(f"Loaded configuration from {config_path}")
                
            except Exception as e:
                logger.warning(f"Failed to load config file {config_path}: {str(e)}")
        else:
            logger.info("No configuration file found, using defaults")
        
        # 环境变量覆盖
        config = self._apply_env_overrides(config)
        
        return config
    
    def _deep_merge(self, base: Dict[str, Any], override: Dict[str, Any]) -> Dict[str, Any]:
        """深度合并字典"""
        result = base.copy()
        
        for key, value in override.items():
            if key in result and isinstance(result[key], dict) and isinstance(value, dict):
                result[key] = self._deep_merge(result[key], value)
            else:
                result[key] = value
        
        return result
    
    def _apply_env_overrides(self, config: Dict[str, Any]) -> Dict[str, Any]:
        """应用环境变量覆盖"""
        env_mappings = {
            'AUDIO_SERVICE_HOST': ('service', 'host'),
            'AUDIO_SERVICE_PORT': ('service', 'port'),
            'AUDIO_SERVICE_DEBUG': ('service', 'debug'),
            'AUDIO_SERVICE_LOG_LEVEL': ('service', 'log_level'),
            'FFMPEG_PATH': ('ffmpeg', 'path'),
            'HLS_OUTPUT_DIR': ('hls', 'output_dir'),
            'IDLE_TIMEOUT': ('idle_process', 'timeout'),
            'LOCK_DIR': ('concurrency', 'lock_dir'),
            'MIN_FREE_SPACE_MB': ('error_handling', 'min_free_space_mb'),
            'AUTO_RECOVERY_ENABLED': ('error_handling', 'auto_recovery_enabled')
        }
        
        for env_var, (section, key) in env_mappings.items():
            value = os.getenv(env_var)
            if value is not None:
                # 类型转换
                if key in ['port', 'segment_duration', 'segment_list_size', 'max_age', 
                          'cleanup_interval', 'lock_timeout', 'timeout', 'check_interval', 
                          'interval', 'max_log_size', 'min_free_space_mb', 'disk_check_interval',
                          'network_retry_delay', 'max_recovery_attempts', 'max_error_history']:
                    try:
                        value = int(value)
                    except ValueError:
                        logger.warning(f"Invalid integer value for {env_var}: {value}")
                        continue
                elif key in ['debug', 'auto_recovery_enabled']:
                    value = value.lower() in ('true', '1', 'yes', 'on')
                
                config[section][key] = value
                logger.debug(f"Applied environment override: {env_var}={value}")
        
        return config
    
    def _validate_config(self):
        """验证配置有效性"""
        errors = []
        
        # 验证 FFmpeg 路径
        ffmpeg_path = self.FFMPEG_PATH
        if not os.path.exists(ffmpeg_path):
            errors.append(f"FFmpeg not found at {ffmpeg_path}")
        elif not os.access(ffmpeg_path, os.X_OK):
            errors.append(f"FFmpeg at {ffmpeg_path} is not executable")
        
        # 验证端口范围
        port = self.PORT
        if not (1 <= port <= 65535):
            errors.append(f"Invalid port number: {port}")
        
        # 验证超时值
        if self.IDLE_TIMEOUT <= 0:
            errors.append(f"Invalid idle timeout: {self.IDLE_TIMEOUT}")
        
        if self.LOCK_TIMEOUT <= 0:
            errors.append(f"Invalid lock timeout: {self.LOCK_TIMEOUT}")
        
        if errors:
            error_msg = "Configuration validation failed:\\n" + "\\n".join(errors)
            raise ValueError(error_msg)
        
        logger.info("Configuration validation passed")
    
    def _create_directories(self):
        """创建必要的目录"""
        directories = [
            self.HLS_OUTPUT_DIR,
            self.LOCK_DIR
        ]
        
        for directory in directories:
            try:
                os.makedirs(directory, exist_ok=True)
                logger.debug(f"Created directory: {directory}")
            except OSError as e:
                logger.error(f"Failed to create directory {directory}: {str(e)}")
                raise
    
    # 服务配置属性
    @property
    def HOST(self) -> str:
        return self._config['service']['host']
    
    @property
    def PORT(self) -> int:
        return self._config['service']['port']
    
    @property
    def DEBUG(self) -> bool:
        return self._config['service']['debug']
    
    @property
    def LOG_LEVEL(self) -> str:
        return self._config['service']['log_level']
    
    # FFmpeg 配置属性
    @property
    def FFMPEG_PATH(self) -> str:
        return self._config['ffmpeg']['path']
    
    @property
    def FFMPEG_AUDIO_CODEC(self) -> str:
        return self._config['ffmpeg']['audio_codec']
    
    @property
    def FFMPEG_BITRATE(self) -> str:
        return self._config['ffmpeg']['bitrate']
    
    @property
    def FFMPEG_PRESET(self) -> str:
        return self._config['ffmpeg']['preset']
    
    @property
    def FFMPEG_TUNE(self) -> str:
        return self._config['ffmpeg']['tune']
    
    # HLS 配置属性
    @property
    def HLS_OUTPUT_DIR(self) -> str:
        return self._config['hls']['output_dir']
    
    @property
    def HLS_PLAYLIST_NAME(self) -> str:
        return self._config['hls']['playlist_name']
    
    @property
    def HLS_SEGMENT_DURATION(self) -> int:
        return self._config['hls']['segment_duration']
    
    @property
    def HLS_SEGMENT_LIST_SIZE(self) -> int:
        return self._config['hls']['segment_list_size']
    
    @property
    def HLS_SEGMENT_PREFIX(self) -> str:
        return self._config['hls']['segment_prefix']
    
    @property
    def HLS_MAX_AGE(self) -> int:
        return self._config['hls']['max_age']
    
    @property
    def HLS_CLEANUP_INTERVAL(self) -> int:
        return self._config['hls']['cleanup_interval']
    
    # 并发控制配置属性
    @property
    def LOCK_DIR(self) -> str:
        return self._config['concurrency']['lock_dir']
    
    @property
    def LOCK_TIMEOUT(self) -> int:
        return self._config['concurrency']['lock_timeout']
    
    # 空闲进程配置属性
    @property
    def IDLE_TIMEOUT(self) -> int:
        return self._config['idle_process']['timeout']
    
    @property
    def IDLE_CHECK_INTERVAL(self) -> int:
        return self._config['idle_process']['check_interval']
    
    # 资源清理配置属性
    @property
    def CLEANUP_INTERVAL(self) -> int:
        return self._config['cleanup']['interval']
    
    @property
    def MAX_LOG_SIZE(self) -> int:
        return self._config['cleanup']['max_log_size']
    
    # 错误处理配置属性
    @property
    def MIN_FREE_SPACE_MB(self) -> int:
        return self._config['error_handling']['min_free_space_mb']
    
    @property
    def MAX_ERROR_HISTORY(self) -> int:
        return self._config['error_handling']['max_error_history']
    
    @property
    def DISK_CHECK_INTERVAL(self) -> int:
        return self._config['error_handling']['disk_check_interval']
    
    @property
    def AUTO_RECOVERY_ENABLED(self) -> bool:
        return self._config['error_handling']['auto_recovery_enabled']
    
    @property
    def NETWORK_RETRY_DELAY(self) -> int:
        return self._config['error_handling']['network_retry_delay']
    
    @property
    def MAX_RECOVERY_ATTEMPTS(self) -> int:
        return self._config['error_handling']['max_recovery_attempts']
    
    def get_config_dict(self) -> Dict[str, Any]:
        """获取完整配置字典"""
        return self._config.copy()


# 创建全局配置实例
config = AudioServiceConfig()
