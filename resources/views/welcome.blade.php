@extends('layouts.app')

@section('title', 'FM网络广播')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-xl p-8 mb-8 text-white">
    <div class="container mx-auto">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">探索精彩FM广播世界</h1>
        <p class="text-lg md:text-xl mb-6 opacity-90">聆听全球优质电台，发现您喜爱的音乐和节目</p>
        
        <!-- Search Bar -->
        <form action="{{ route('search') }}" method="GET" class="max-w-3xl">
            <div class="relative">
                <input type="text" name="keyword" placeholder="搜索您喜爱的电台或节目..." 
                       class="w-full pl-12 pr-4 py-3 rounded-full text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent text-lg">
                <button type="submit" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Featured Channels -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
        <svg class="w-6 h-6 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
        推荐频道
    </h2>
    
    @if($featuredChannels->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($featuredChannels as $channel)
                <div class="bg-gray-50 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
                    @if($channel->logo_url)
                        <img src="{{ $channel->logo_url }}" alt="{{ $channel->title }}" 
                             class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 mb-4 mx-auto">
                    @else
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-lg mb-4 mx-auto">
                            {{ Str::substr($channel->title, 0, 2) }}
                        </div>
                    @endif
                    
                    <h3 class="text-lg font-semibold text-center mb-2 text-gray-800">{{ $channel->title }}</h3>
                    <p class="text-sm text-center text-gray-600 mb-4">{{ Str::limit($channel->description, 50) }}</p>
                    
                    <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors channel-item" 
                            data-channel-id="{{ $channel->id }}" 
                            data-channel-title="{{ $channel->title }}" 
                            data-channel-logo="{{ $channel->logo_url }}">
                        <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/>
                        </svg>
                        立即播放
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-800 mb-2">暂无推荐频道</h3>
            <p class="text-gray-600">频道即将上线，敬请期待。</p>
        </div>
    @endif
</div>

<!-- Categories Section -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
        </svg>
        频道分类
    </h2>
    
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($parentCategories as $category)
            <a href="{{ route('categories.show', $category->slug) }}" 
               class="bg-gray-50 rounded-lg p-4 text-center hover:bg-blue-50 transition-colors shadow-sm hover:shadow-md">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-base mb-3 mx-auto">
                    {{ Str::substr($category->name, 0, 2) }}
                </div>
                <h3 class="text-sm font-medium text-gray-800">{{ $category->name }}</h3>
            </a>
        @endforeach
        
        <a href="{{ route('categories.index') }}" 
           class="bg-gray-50 rounded-lg p-4 text-center hover:bg-blue-50 transition-colors shadow-sm hover:shadow-md">
            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-medium text-base mb-3 mx-auto">
                更多
            </div>
            <h3 class="text-sm font-medium text-gray-800">查看全部</h3>
        </a>
    </div>
    
    <div class="text-center mt-8">
        <a href="{{ route('categories.index') }}" 
           class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            浏览所有分类
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Handle channel selection
    document.querySelectorAll('.channel-item').forEach(item => {
            item.addEventListener('click', function() {
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
        });
</script>
@endsection