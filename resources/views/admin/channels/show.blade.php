@extends('admin.layouts.app')

@section('title', '频道详情')
@section('page-title', '频道详情')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">频道详情</h2>
        <a href="{{ route('admin.channels.edit', $channel->id) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            编辑
        </a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-medium text-gray-800 mb-4">基本信息</h3>
            
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">频道名称</p>
                    <p class="font-medium text-gray-800">{{ $channel->title }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">分类</p>
                    <p class="font-medium text-gray-800">{{ $channel->category->name }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">状态</p>
                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                               {{ $channel->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $channel->is_active ? '活跃' : '停用' }}
                    </span>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">是否批准</p>
                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                               {{ $channel->is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $channel->is_approved ? '已批准' : '待批准' }}
                    </span>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">听众数</p>
                    <p class="font-medium text-gray-800">{{ $channel->listeners }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">添加时间</p>
                    <p class="font-medium text-gray-800">{{ $channel->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-medium text-gray-800 mb-4">详细信息</h3>
            
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">流地址</p>
                    <a href="{{ $channel->stream_url }}" target="_blank" 
                       class="font-medium text-blue-600 hover:text-blue-800 transition-colors break-all">
                        {{ $channel->stream_url }}
                    </a>
                </div>
                
                @if($channel->logo_url)
                    <div>
                        <p class="text-sm text-gray-500">Logo URL</p>
                        <a href="{{ $channel->logo_url }}" target="_blank" 
                           class="font-medium text-blue-600 hover:text-blue-800 transition-colors break-all">
                            {{ $channel->logo_url }}
                        </a>
                    </div>
                @endif
                
                @if($channel->country)
                    <div>
                        <p class="text-sm text-gray-500">国家</p>
                        <p class="font-medium text-gray-800">{{ $channel->country }}</p>
                    </div>
                @endif
                
                @if($channel->language)
                    <div>
                        <p class="text-sm text-gray-500">语言</p>
                        <p class="font-medium text-gray-800">{{ $channel->language }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="mt-6">
        <h3 class="text-lg font-medium text-gray-800 mb-4">描述</h3>
        <p class="text-gray-700 whitespace-pre-line">{{ $channel->description }}</p>
    </div>
    
    <div class="mt-6 flex justify-end space-x-4">
        <a href="{{ route('admin.channels.index') }}" 
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            返回列表
        </a>
    </div>
</div>
@endsection