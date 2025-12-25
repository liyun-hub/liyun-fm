@extends('layouts.app')

@section('title', $category->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-5 sticky top-8">
            <h3 class="text-lg font-semibold mb-4 text-gray-800">分类导航</h3>
            <ul class="space-y-2">
                @foreach($parentCategories as $parent)
                    <li>
                        <a href="{{ route('categories.show', $parent->slug) }}" 
                           class="block py-2 px-3 rounded-lg hover:bg-blue-50 transition-colors {{ $category->id == $parent->id ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700' }}">
                            {{ $parent->name }}
                        </a>
                        
                        @if($parent->children->count() > 0)
                            <ul class="ml-4 space-y-1 mt-1">
                                @foreach($parent->children as $child)
                                    <li>
                                        <a href="{{ route('categories.show', $child->slug) }}" 
                                           class="block py-1.5 px-3 rounded-lg text-sm hover:bg-blue-50 transition-colors {{ $category->id == $child->id ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-600' }}">
                                            {{ $child->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-3xl font-bold mb-4 text-gray-800">{{ $category->name }}</h1>
            
            @if($category->description)
                <p class="text-gray-600 mb-6">{{ $category->description }}</p>
            @endif
            
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-800 mb-2">暂无频道</h3>
                    <p class="text-gray-600">该分类下还没有频道，敬请期待。</p>
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