@extends('admin.layouts.app')

@section('title', '添加频道')
@section('page-title', '添加频道')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">添加新频道</h2>
    
    <form method="POST" action="{{ route('admin.channels.store') }}">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                <label for="title" class="block text-gray-700 mb-2">频道名称</label>
                <input type="text" id="title" name="title" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       required>
            </div>
            
            <div>
                <label for="category_id" class="block text-gray-700 mb-2">分类</label>
                <select id="category_id" name="category_id" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        required>
                    <option value="">选择分类</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="stream_url" class="block text-gray-700 mb-2">流地址</label>
            <input type="url" id="stream_url" name="stream_url" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   required>
        </div>
        
        <div class="mb-4">
            <label for="logo_url" class="block text-gray-700 mb-2">Logo URL (可选)</label>
            <input type="url" id="logo_url" name="logo_url" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                <label for="country" class="block text-gray-700 mb-2">国家 (可选)</label>
                <input type="text" id="country" name="country" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label for="language" class="block text-gray-700 mb-2">语言 (可选)</label>
                <input type="text" id="language" name="language" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 mb-2">描述</label>
            <textarea id="description" name="description" rows="4" 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                      required></textarea>
        </div>
        
        <div class="flex items-center space-x-4 mb-6">
            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" 
                       class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                       checked>
                <label for="is_active" class="ml-2 text-gray-700">活跃</label>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="is_approved" name="is_approved" 
                       class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                       checked>
                <label for="is_approved" class="ml-2 text-gray-700">已批准</label>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="proxy_enabled" name="proxy_enabled" 
                       class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                       checked>
                <label for="proxy_enabled" class="ml-2 text-gray-700">启用代理</label>
            </div>
        </div>
        
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('admin.channels.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                取消
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                保存
            </button>
        </div>
    </form>
</div>
@endsection