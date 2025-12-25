@extends('admin.layouts.app')

@section('title', '分类管理')
@section('page-title', '分类管理')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">分类列表</h2>
        <a href="{{ route('admin.categories.create') }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            添加分类
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">分类名称</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">父分类</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">状态</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">顺序</th>
                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">添加时间</th>
                    <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-sm text-gray-800">{{ $category->name }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $category->parent ? $category->parent->name : '无' }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-medium 
                                       {{ $category->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $category->status ? '启用' : '禁用' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $category->order }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $category->created_at->format('Y-m-d H:i') }}</td>
                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('admin.categories.edit', $category->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 transition-colors">
                                    编辑
                                </a>
                                
                                <a href="{{ route('admin.categories.show', $category->id) }}" 
                                   class="text-indigo-600 hover:text-indigo-800 transition-colors">
                                    查看
                                </a>
                                
                                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800 transition-colors" 
                                            onclick="return confirm('确定要删除该分类吗？删除后该分类下的所有频道将被移除分类。')">
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
        {{ $categories->links() }}
    </div>
</div>
@endsection