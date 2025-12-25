@extends('layouts.app')

@section('title', '频道分类')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">频道分类</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
            <div class="bg-gray-50 rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow">
                <h2 class="text-xl font-semibold mb-3 text-blue-600">{{ $category->name }}</h2>
                
                @if($category->description)
                    <p class="text-gray-600 mb-4">{{ Str::limit($category->description, 100) }}</p>
                @endif
                
                @if($category->children->count() > 0)
                    <div class="space-y-2">
                        @foreach($category->children as $child)
                            <a href="{{ route('categories.show', $child->slug) }}" 
                               class="block text-gray-700 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                                {{ $child->name }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ route('categories.show', $category->slug) }}" 
                       class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        查看频道
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection