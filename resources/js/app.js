require('./bootstrap');

// Import jquery-pjax
import $ from 'jquery';
import pjax from 'jquery-pjax';

// Configure pjax for smooth page transitions
if (typeof $ !== 'undefined' && typeof document !== 'undefined') {
    // Set up pjax to work with main content area only
    $(document).pjax('a:not([data-no-pjax])', 'main', {
        timeout: 10000,
        fragment: 'main',
        scrollTo: false
    });
    
    // Handle pjax events to maintain player state
    $(document).on('pjax:start', () => {
        console.log('pjax:start - Page transition started');
        // Player will continue playing automatically since it's in a fixed position
    });
    
    $(document).on('pjax:end', () => {
        console.log('pjax:end - Page transition completed');
        // No need to re-initialize event listeners with event delegation
    });
    
    // Add event delegation for channel item clicks
    $(document).on('click', '.channel-item', function(e) {
        e.preventDefault();
        const channelId = this.dataset.channelId;
        const channelTitle = this.dataset.channelTitle;
        const channelLogo = this.dataset.channelLogo;
        
        // Send channel information to the player
        const event = new CustomEvent('channelSelected', {
            detail: {
                id: channelId,
                title: channelTitle,
                logo: channelLogo
            }
        });
        window.dispatchEvent(event);
    });
    
    $(document).on('pjax:error', (xhr, textStatus, errorThrown) => {
        console.error('pjax:error - Page transition failed:', textStatus, errorThrown);
    });
}

// Wait for DPlayer to be available before initializing
function waitForDPlayer(callback) {
    if (typeof DPlayer !== 'undefined') {
        callback();
    } else {
        setTimeout(() => waitForDPlayer(callback), 100);
    }
}

// FM Radio Player
class FMRadioPlayer {
    constructor() {
        this.dp = null;
        this.isPlaying = false;
        this.currentChannel = null;
        this.volume = 0.8; // Default volume (0-1)
        
        this.initElements();
        this.initEventListeners();
        
        // Wait for DPlayer to be available before initializing
        waitForDPlayer(() => {
            this.initDPlayer();
        });
    }
    
    initElements() {
        this.playPauseBtn = document.getElementById('play-pause-btn');
        if (!this.playPauseBtn) console.warn('play-pause-btn element not found');
        
        this.playIcon = document.getElementById('play-icon');
        if (!this.playIcon) console.warn('play-icon element not found');
        
        this.pauseIcon = document.getElementById('pause-icon');
        if (!this.pauseIcon) console.warn('pause-icon element not found');
        
        this.prevBtn = document.getElementById('prev-btn');
        if (!this.prevBtn) console.warn('prev-btn element not found');
        
        this.nextBtn = document.getElementById('next-btn');
        if (!this.nextBtn) console.warn('next-btn element not found');
        
        this.volumeBtn = document.getElementById('volume-btn');
        if (!this.volumeBtn) console.warn('volume-btn element not found');
        
        this.volumeSlider = document.getElementById('volume-slider');
        if (!this.volumeSlider) console.warn('volume-slider element not found');
        
        this.playerTitle = document.getElementById('player-title');
        if (!this.playerTitle) console.warn('player-title element not found');
        
        this.playerDescription = document.getElementById('player-description');
        if (!this.playerDescription) console.warn('player-description element not found');
        
        this.playerThumbnail = document.getElementById('player-thumbnail');
        if (!this.playerThumbnail) console.warn('player-thumbnail element not found');
        
        this.playerThumbnailImg = document.getElementById('player-thumbnail-img');
        if (!this.playerThumbnailImg) console.warn('player-thumbnail-img element not found');
        
        this.playerThumbnailIcon = document.getElementById('player-thumbnail-icon');
        if (!this.playerThumbnailIcon) console.warn('player-thumbnail-icon element not found');
    }
    
    initEventListeners() {
        // Play/pause button click
        if (this.playPauseBtn) {
            this.playPauseBtn.addEventListener('click', () => this.togglePlayPause());
        }
        
        // Volume slider change
        if (this.volumeSlider) {
            this.volumeSlider.addEventListener('input', (e) => this.setVolume(e.target.value));
        }
        
        // Channel selected event
        window.addEventListener('channelSelected', (e) => this.playChannel(e.detail));
    }
    
    initDPlayer() {
        // Check if DPlayer is available
        if (typeof DPlayer === 'undefined') {
            console.error('DPlayer is not loaded. Please check if DPlayer.min.js is properly included.');
            if (this.playerDescription) {
                this.playerDescription.textContent = 'DPlayer 加载失败，播放功能不可用';
            }
            return;
        }
        
        console.log('DPlayer is available, initializing...');
        
        // Create a hidden container for DPlayer
        this.dpContainer = document.createElement('div');
        this.dpContainer.id = 'dplayer-container';
        this.dpContainer.style.display = 'none';
        document.body.appendChild(this.dpContainer);
        
        try {
            // Initialize DPlayer with proper video configuration
            this.dp = new DPlayer({
                container: this.dpContainer,
                preload: 'auto',
                volume: this.volume,
                autoplay: false,
                screenshot: false,
                hotkey: false,
                mutex: false,
                lang: 'zh-cn',
                video: {
                    url: '',  // 空的初始URL
                    type: 'auto'
                }
            });
            
            console.log('DPlayer initialized successfully');
            
            // Set initial volume
            if (this.volumeSlider) {
                this.volumeSlider.value = this.volume * 100;
            }
            
            // Listen for DPlayer events
            this.dp.on('play', () => {
                this.isPlaying = true;
                this.updatePlayPauseIcon();
                console.log('DPlayer: play event');
                if (this.playerDescription) {
                    this.playerDescription.textContent = '正在播放 ' + (this.currentChannel ? this.currentChannel.title : '');
                }
            });
            
            this.dp.on('pause', () => {
                this.isPlaying = false;
                this.updatePlayPauseIcon();
                console.log('DPlayer: pause event');
            });
            
            this.dp.on('loadstart', () => {
                console.log('DPlayer: loadstart event');
                if (this.playerDescription) {
                    this.playerDescription.textContent = '正在加载...';
                }
            });
            
            this.dp.on('loadeddata', () => {
                console.log('DPlayer: loadeddata event');
                if (this.playerDescription) {
                    this.playerDescription.textContent = '加载完成，准备播放';
                }
            });
            
            this.dp.on('canplay', () => {
                console.log('DPlayer: canplay event');
                if (this.playerDescription) {
                    this.playerDescription.textContent = '可以播放';
                }
            });
            
            this.dp.on('waiting', () => {
                console.log('DPlayer: waiting event (buffering)');
                if (this.playerDescription) {
                    this.playerDescription.textContent = '缓冲中...';
                }
            });
            
            this.dp.on('error', (error) => {
                console.error('DPlayer error:', error);
                
                // 更详细的错误处理
                let errorMessage = '播放出错，请重试';
                
                if (error && error.type) {
                    switch (error.type) {
                        case 'networkError':
                            errorMessage = '网络连接错误，请检查网络';
                            break;
                        case 'mediaError':
                            if (error.details === 'bufferStalledError') {
                                console.log('Buffer stalled, attempting to reload...');
                                if (this.currentChannel) {
                                    setTimeout(() => {
                                        this.playChannel(this.currentChannel);
                                    }, 2000);
                                    return;
                                }
                            } else {
                                errorMessage = '媒体格式错误或音频流不可用';
                            }
                            break;
                        case 'muxError':
                            errorMessage = '音频流解析错误';
                            break;
                        default:
                            errorMessage = `播放错误: ${error.type}`;
                    }
                } else if (error && error.message) {
                    errorMessage = error.message;
                }
                
                this.onError(errorMessage);
            });
            
            // 添加HLS特定事件监听（简化版）
            if (this.dp.hls && typeof Hls !== 'undefined') {
                this.dp.hls.on(Hls.Events.ERROR, (event, data) => {
                    console.error('HLS error:', data);
                    if (data.fatal) {
                        switch (data.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                console.log('Fatal network error encountered, trying to recover...');
                                this.dp.hls.startLoad();
                                break;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                console.log('Fatal media error encountered, trying to recover...');
                                this.dp.hls.recoverMediaError();
                                break;
                            default:
                                console.log('Fatal error, cannot recover');
                                this.onError('播放出现致命错误，无法恢复');
                                break;
                        }
                    }
                });
            }
            
            // Update player description
            if (this.playerDescription) {
                this.playerDescription.textContent = '点击频道开始播放';
            }
            
        } catch (error) {
            console.error('Failed to initialize DPlayer:', error);
            if (this.playerDescription) {
                this.playerDescription.textContent = 'DPlayer 初始化失败';
            }
        }
    }
    
    updatePlayPauseIcon() {
        if (this.playIcon && this.pauseIcon) {
            if (this.isPlaying) {
                this.playIcon.classList.add('hidden');
                this.pauseIcon.classList.remove('hidden');
            } else {
                this.playIcon.classList.remove('hidden');
                this.pauseIcon.classList.add('hidden');
            }
        }
    }
    
    async playChannel(channel) {
        console.log('Playing channel:', channel);
        this.currentChannel = channel;
        
        // Update player UI if elements exist
        if (this.playerTitle) {
            this.playerTitle.textContent = channel.title;
        }
        if (this.playerDescription) {
            this.playerDescription.textContent = '正在获取播放链接...';
        }
        
        // Update channel logo
        if (this.playerThumbnailImg && this.playerThumbnailIcon) {
            const logoUrl = channel.logo || channel.logo_url;
            if (logoUrl) {
                this.playerThumbnailImg.src = logoUrl;
                this.playerThumbnailImg.classList.remove('hidden');
                this.playerThumbnailIcon.classList.add('hidden');
                this.playerThumbnail.classList.remove('bg-gray-200', 'flex', 'items-center', 'justify-center', 'text-gray-500');
            } else {
                this.playerThumbnailImg.src = '';
                this.playerThumbnailImg.classList.add('hidden');
                this.playerThumbnailIcon.classList.remove('hidden');
                this.playerThumbnail.classList.add('bg-gray-200', 'flex', 'items-center', 'justify-center', 'text-gray-500');
            }
        }
        
        try {
            // Call API to get play URL
            console.log('Fetching play URL for channel:', channel.id);
            const response = await fetch(`/api/play/${channel.id}?hls=true`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            
            console.log('API response status:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API response error:', errorText);
                throw new Error(`获取播放链接失败 (${response.status})`);
            }
            
            const data = await response.json();
            console.log('API response data:', data);
            
            if (data.code !== 200) {
                throw new Error(data.message || '获取播放链接失败');
            }
            
            const finalUrl = data.data.play_url;
            console.log('Got play URL:', finalUrl);
            
            // Use logo from API response if available
            const logoUrl = data.data.logo || channel.logo || channel.logo_url;
            
            // Update channel logo again with API data
            if (this.playerThumbnailImg && this.playerThumbnailIcon) {
                if (logoUrl) {
                    this.playerThumbnailImg.src = logoUrl;
                    this.playerThumbnailImg.classList.remove('hidden');
                    this.playerThumbnailIcon.classList.add('hidden');
                    this.playerThumbnail.classList.remove('bg-gray-200', 'flex', 'items-center', 'justify-center', 'text-gray-500');
                } else {
                    this.playerThumbnailImg.src = '';
                    this.playerThumbnailImg.classList.add('hidden');
                    this.playerThumbnailIcon.classList.remove('hidden');
                    this.playerThumbnail.classList.add('bg-gray-200', 'flex', 'items-center', 'justify-center', 'text-gray-500');
                }
            }
            
            // Update DPlayer source with the stream URL
            if (!this.dp) {
                throw new Error('播放器未初始化');
            }
            
            if (this.playerDescription) {
                this.playerDescription.textContent = '正在加载播放器...';
            }
            
            console.log('Switching DPlayer video to:', finalUrl);
            
            // 使用与测试页面相同的配置
            const videoConfig = {
                url: finalUrl,
                type: 'hls',  // 强制使用 HLS 类型
                name: channel.title
            };
            
            console.log('DPlayer video config:', videoConfig);
            
            this.dp.switchVideo(videoConfig);
            
            if (this.playerDescription) {
                this.playerDescription.textContent = '正在连接音频流...';
            }
            
            // 自动播放
            setTimeout(() => {
                this.play();
            }, 500);
            
        } catch (error) {
            console.error('播放错误:', error);
            this.onError(error.message || '播放失败，请稍后重试');
        }
    }

    
    togglePlayPause() {
        console.log('Toggle play/pause, current state:', this.isPlaying);
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    play() {
        if (!this.currentChannel) {
            console.log('No channel selected');
            return; // No channel selected
        }
        
        if (!this.dp) {
            console.error('DPlayer is not initialized');
            this.onError('播放器未初始化');
            return;
        }
        
        console.log('Starting playback');
        try {
            this.dp.play();
        } catch (error) {
            console.error('Play error:', error);
            this.onError('播放启动失败');
        }
    }
    
    pause() {
        if (!this.dp) {
            console.error('DPlayer is not initialized');
            return;
        }
        
        console.log('Pausing playback');
        try {
            this.dp.pause();
        } catch (error) {
            console.error('Pause error:', error);
        }
    }
    
    setVolume(volumeValue) {
        this.volume = volumeValue / 100;
        
        if (!this.dp) {
            console.error('DPlayer is not initialized');
            return;
        }
        
        console.log('Setting volume to:', this.volume);
        try {
            this.dp.volume(this.volume);
        } catch (error) {
            console.error('Volume error:', error);
        }
    }
    
    onTrackEnded() {
        this.isPlaying = false;
        this.updatePlayPauseIcon();
    }
    
    onError(customMessage = '播放出错，请重试') {
        console.error('Audio error occurred:', customMessage);
        if (this.playerDescription) {
            this.playerDescription.textContent = customMessage;
        }
        this.isPlaying = false;
        this.updatePlayPauseIcon();
    }
    
    // Placeholder methods for next/previous functionality
    // These would be implemented if we had a playlist feature
    next() {
        console.log('Next channel');
    }
    
    previous() {
        console.log('Previous channel');
    }
    
    // 尝试直接播放模式（非 HLS）
    async tryDirectPlay() {
        if (!this.currentChannel) {
            return;
        }
        
        console.log('尝试直接播放模式');
        
        try {
            // 获取非 HLS 播放链接
            const response = await fetch(`/api/play/${this.currentChannel.id}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.code === 200) {
                    const directUrl = data.data.play_url;
                    console.log('使用直接播放链接:', directUrl);
                    
                    this.dp.switchVideo({
                        url: directUrl,
                        type: 'auto',
                        name: this.currentChannel.title
                    });
                    
                    if (this.playerDescription) {
                        this.playerDescription.textContent = '使用直接播放模式';
                    }
                    
                    setTimeout(() => {
                        this.play();
                    }, 1000);
                    
                    return;
                }
            }
        } catch (error) {
            console.error('直接播放模式也失败:', error);
        }
        
        // 如果直接播放也失败，显示错误
        this.onError('所有播放模式都失败，请稍后重试');
    }
}

// Initialize the player when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing FM Radio Player');
    window.fmPlayer = new FMRadioPlayer();
});