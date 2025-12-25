@extends('admin.layouts.app')

@section('title', '仪表盘')
@section('page-title', '仪表盘')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Channels Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 mb-1">总频道数</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $totalChannels }}</h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- Active Channels Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 mb-1">活跃频道</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $activeChannels }}</h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- Pending Channels Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 mb-1">待批准频道</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $pendingChannels }}</h3>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- Total Categories Card -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 mb-1">总分类数</p>
                <h3 class="text-3xl font-bold text-gray-800">{{ $totalCategories }}</h3>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Recent Channels -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">最近添加的频道</h2>
        <a href="{{ route('admin.channels.index') }}" 
           class="text-blue-600 hover:text-blue-800 transition-colors">
            查看全部
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">频道名称</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">分类</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">状态</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">添加时间</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentChannels as $channel)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-sm text-gray-800">{{ $channel->title }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $channel->category->name }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                                       {{ $channel->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $channel->is_active ? '活跃' : '停用' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $channel->created_at->format('Y-m-d H:i') }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('admin.channels.edit', $channel->id) }}" 
                               class="text-blue-600 hover:text-blue-800 transition-colors mr-3">
                                编辑
                            </a>
                            <a href="{{ route('admin.channels.show', $channel->id) }}" 
                               class="text-green-600 hover:text-green-800 transition-colors">
                                查看
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">快速操作</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('admin.channels.create') }}" 
           class="flex items-center space-x-4 p-5 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-800">添加新频道</h3>
                <p class="text-gray-600 mt-1">创建一个新的广播频道</p>
            </div>
        </a>
        
        <a href="{{ route('admin.categories.create') }}" 
           class="flex items-center space-x-4 p-5 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-800">添加新分类</h3>
                <p class="text-gray-600 mt-1">创建一个新的频道分类</p>
            </div>
        </a>
    </div>
</div>
@endsection