@extends('admin.layouts.app')

@section('title', '分类详情')
@section('page-title', '分类详情')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">分类详情</h2>
        <a href="{{ route('admin.categories.edit', $category->id) }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            编辑
        </a>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-medium text-gray-800 mb-4">基本信息</h3>
            
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">分类名称</p>
                    <p class="font-medium text-gray-800">{{ $category->name }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">父分类</p>
                    <p class="font-medium text-gray-800">{{ $category->parent ? $category->parent->name : '无' }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">状态</p>
                    <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                               {{ $category->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $category->status ? '启用' : '禁用' }}
                    </span>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">顺序</p>
                    <p class="font-medium text-gray-800">{{ $category->order }}</p>
                </div>
                
                @if($category->icon)
                    <div>
                        <p class="text-sm text-gray-500">图标</p>
                        <p class="font-medium text-gray-800">{{ $category->icon }}</p>
                    </div>
                @endif
                
                <div>
                    <p class="text-sm text-gray-500">添加时间</p>
                    <p class="font-medium text-gray-800">{{ $category->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-medium text-gray-800 mb-4">频道统计</h3>
            
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">总频道数</p>
                    <p class="font-medium text-gray-800">{{ $category->channels->count() }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">活跃频道数</p>
                    <p class="font-medium text-gray-800">{{ $category->channels->where('is_active', true)->count() }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">已批准频道数</p>
                    <p class="font-medium text-gray-800">{{ $category->channels->where('is_approved', true)->count() }}</p>
                </div>
            </div>
            
            @if($category->children->count() > 0)
                <h3 class="text-lg font-medium text-gray-800 mt-6 mb-4">子分类</h3>
                <ul class="space-y-2">
                    @foreach($category->children as $child)
                        <li>
                            <a href="{{ route('admin.categories.show', $child->id) }}" 
                               class="text-blue-600 hover:text-blue-800 transition-colors">
                                {{ $child->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    
    @if($category->description)
        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">描述</h3>
            <p class="text-gray-700 whitespace-pre-line">{{ $category->description }}</p>
        </div>
    @endif
    
    <div class="mt-6 flex justify-end space-x-4">
        <a href="{{ route('admin.categories.index') }}" 
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            返回列表
        </a>
    </div>
</div>
@endsection