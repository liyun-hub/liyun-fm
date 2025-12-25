@extends('admin.layouts.app')

@section('title', '编辑分类')
@section('page-title', '编辑分类')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">编辑分类</h2>
    
    <form method="POST" action="{{ route('admin.categories.update', $category->id) }}">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                <label for="name" class="block text-gray-700 mb-2">分类名称</label>
                <input type="text" id="name" name="name" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       value="{{ $category->name }}" required>
            </div>
            
            <div>
                <label for="parent_id" class="block text-gray-700 mb-2">父分类</label>
                <select id="parent_id" name="parent_id" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">选择父分类 (可选)</option>
                    @foreach($parentCategories as $parent)
                        <option value="{{ $parent->id }}" {{ $category->parent_id == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                <label for="order" class="block text-gray-700 mb-2">顺序</label>
                <input type="number" id="order" name="order" min="0" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       value="{{ $category->order }}">
            </div>
            
            <div>
                <label for="icon" class="block text-gray-700 mb-2">图标 (可选)</label>
                <input type="text" id="icon" name="icon" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       value="{{ $category->icon }}" placeholder="例如: music">
            </div>
        </div>
        
        <div class="mb-4">
            <label for="description" class="block text-gray-700 mb-2">描述 (可选)</label>
            <textarea id="description" name="description" rows="4" 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ $category->description }}</textarea>
        </div>
        
        <div class="flex items-center mb-6">
            <input type="checkbox" id="status" name="status" 
                   class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                   {{ $category->status ? 'checked' : '' }}>
            <label for="status" class="ml-2 text-gray-700">启用</label>
        </div>
        
        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('admin.categories.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                取消
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                更新
            </button>
        </div>
    </form>
</div>
@endsection