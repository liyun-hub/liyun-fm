<div id="player" class="fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t border-gray-200 z-50">

<style>
    /* Volume control hover effect */
    #player .flex.items-center.space-x-2.ml-4:hover #volume-container {
        width: 5rem;
    }
    
    /* Hide DPlayer native controls completely */
    #dplayer-container {
        display: none !important;
    }
    
    /* Ensure volume slider only shows on hover */
    #volume-container {
        opacity: 0;
        transition: all 0.3s ease-in-out;
    }
    
    #player .flex.items-center.space-x-2.ml-4:hover #volume-container {
        opacity: 1;
        width: 5rem;
    }
    
    /* Responsive fix for vertical screen */
    @media (max-width: 768px) {
        #player {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        #player .flex.items-center.space-x-4.flex-1.min-w-0 {
            min-width: auto;
            flex: none;
            margin-right: 1rem;
        }
        
        #player .flex.items-center.space-x-6 {
            flex: none;
        }
        
        #player-title {
            font-size: 0.875rem;
        }
        
        #player-description {
            font-size: 0.75rem;
        }
    }
</style>
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center">
            <!-- Current Playing Info -->
            <div class="flex items-center space-x-4 flex-1 min-w-0 max-w-[60%]">
                <div id="player-thumbnail" class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center text-gray-500 overflow-hidden">
                    <img id="player-thumbnail-img" src="" alt="Channel Logo" class="w-full h-full object-cover hidden">
                    <svg id="player-thumbnail-icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                
                <div class="flex-1 min-w-0">
                    <h3 id="player-title" class="text-sm font-semibold text-gray-800 truncate">未选择频道</h3>
                    <p id="player-description" class="text-xs text-gray-500 truncate">点击频道开始播放</p>
                </div>
            </div>
            
            <!-- Player Controls -->
            <div class="flex items-center space-x-6 mx-auto">
                <button id="prev-btn" class="text-gray-500 hover:text-gray-800 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                
                <button id="play-pause-btn" class="bg-blue-600 text-white p-3 rounded-full hover:bg-blue-700 transition-colors shadow-md">
                    <svg id="play-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <svg id="pause-icon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                    </svg>
                </button>
                
                <button id="next-btn" class="text-gray-500 hover:text-gray-800 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            
            <!-- Volume Control -->
            <div class="flex items-center space-x-2 ml-4">
                <button id="volume-btn" class="text-gray-500 hover:text-gray-800 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                    </svg>
                </button>
                <div id="volume-container" class="w-0 overflow-hidden transition-all duration-300 ease-in-out">
                    <input type="range" id="volume-slider" min="0" max="100" value="80" 
                           class="w-20 h-1 bg-gray-200 rounded-full appearance-none cursor-pointer">
                </div>
            </div>
        </div>
    </div>
</div>