@extends('admin.layouts.app')

@section('title', '频道管理')
@section('page-title', '频道管理')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">频道列表</h2>
        <a href="{{ route('admin.channels.create') }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            添加频道
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">频道名称</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">分类</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">状态</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">是否批准</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">听众数</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">添加时间</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($channels as $channel)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-sm text-gray-800">{{ $channel->title }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $channel->category->name }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                                       {{ $channel->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $channel->is_active ? '活跃' : '停用' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                                       {{ $channel->is_approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $channel->is_approved ? '已批准' : '待批准' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $channel->listeners }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $channel->created_at->format('Y-m-d H:i') }}</td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @if(!$channel->is_approved)
                                    <form action="{{ route('admin.channels.approve', $channel->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="text-green-600 hover:text-green-800 transition-colors">
                                            批准
                                        </button>
                                    </form>
                                @endif
                                
                                <form action="{{ route('admin.channels.toggle-status', $channel->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="text-yellow-600 hover:text-yellow-800 transition-colors">
                                        {{ $channel->is_active ? '停用' : '激活' }}
                                    </button>
                                </form>
                                
                                <a href="{{ route('admin.channels.edit', $channel->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 transition-colors">
                                    编辑
                                </a>
                                
                                <a href="{{ route('admin.channels.show', $channel->id) }}" 
                                   class="text-indigo-600 hover:text-indigo-800 transition-colors">
                                    查看
                                </a>
                                
                                <form action="{{ route('admin.channels.destroy', $channel->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800 transition-colors" 
                                            onclick="return confirm('确定要删除该频道吗？')">
                                        删除
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        {{ $channels->links() }}
    </div>
</div>
@endsection