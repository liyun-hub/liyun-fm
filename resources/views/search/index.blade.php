@extends('layouts.app')

@section('title', '搜索结果')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-5 sticky top-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">搜索过滤</h3>
            
            <!-- Category Filter -->
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">分类</h4>
                <form action="{{ route('search') }}" method="GET" class="space-y-2">
                    <input type="hidden" name="keyword" value="{{ $keyword }}">
                    
                    <div class="flex items-center">
                        <input type="radio" id="category-all" name="category" value="" 
                               class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                               {{ empty($categoryId) ? 'checked' : '' }}>
                        <label for="category-all" class="ml-2 text-sm text-gray-700">全部</label>
                    </div>
                    
                    @foreach($categories as $category)
                        <div class="flex items-center">
                            <input type="radio" id="category-{{ $category->id }}" name="category" value="{{ $category->id }}" 
                                   class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                                   {{ $categoryId == $category->id ? 'checked' : '' }}>
                            <label for="category-{{ $category->id }}" class="ml-2 text-sm text-gray-700">{{ $category->name }}</label>
                        </div>
                    @endforeach
                    
                    <button type="submit" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                        应用过滤
                    </button>
                </form>
            </div>
            
            <!-- Popular Searches -->
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-3">热门搜索</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($popularKeywords as $popularKeyword)
                        <a href="{{ route('search', ['keyword' => $popularKeyword]) }}" 
                           class="inline-block px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs hover:bg-blue-50 hover:text-blue-600 transition-colors">
                            {{ $popularKeyword }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Results -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">搜索结果</h1>
                <p class="text-gray-600 mt-1">
                    @if($keyword)
                        搜索关键词: <span class="font-medium text-blue-600">{{ $keyword }}</span>
                        @if($categoryId)
                            , 分类: <span class="font-medium text-blue-600">{{ $categories->find($categoryId)->name }}</span>
                        @endif
                    @else
                        显示全部频道
                    @endif
                </p>
            </div>
            
            @if($channels->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($channels as $channel)
                        <div class="bg-gray-50 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
                            <div class="flex items-center space-x-4">
                                @if($channel->logo_url)
                                    <img src="{{ $channel->logo_url }}" alt="{{ $channel->title }}" 
                                         class="w-16 h-16 rounded-full object-cover border-2 border-gray-200">
                                @else
                                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-lg">
                                        {{ Str::substr($channel->title, 0, 2) }}
                                    </div>
                                @endif
                                
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 hover:text-blue-600 transition-colors cursor-pointer channel-item" 
                                        data-channel-id="{{ $channel->id }}" 
                                        data-channel-title="{{ $channel->title }}" 
                                        data-channel-logo="{{ $channel->logo_url }}">
                                        {{ $channel->title }}
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ $channel->listeners }} 听众</p>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-600 mt-3 line-clamp-2">{{ $channel->description }}</p>
                            
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-xs text-gray-500">{{ $channel->language }}</span>
                                <button class="text-blue-600 hover:text-blue-800 transition-colors channel-item" 
                                        data-channel-id="{{ $channel->id }}" 
                                        data-channel-title="{{ $channel->title }}" 
                                        data-channel-logo="{{ $channel->logo_url }}">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="mt-8">
                    {{ $channels->links() }}
                </div>
            @else
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-800 mb-2">未找到匹配的频道</h3>
                    <p class="text-gray-600">尝试使用其他关键词或分类进行搜索。</p>
                </div>
            @endif
        </div>
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