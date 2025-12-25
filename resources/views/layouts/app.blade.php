<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FM网络广播')</title>
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    @yield('styles')
</head>
<body class="bg-gray-50 text-gray-900">
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-blue-600">FM广播</a>
                <nav class="hidden md:flex space-x-6">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 transition-colors">首页</a>
                    <a href="{{ route('categories.index') }}" class="text-gray-700 hover:text-blue-600 transition-colors">分类</a>
                </nav>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Search Form -->
                <form action="{{ route('search') }}" method="GET" class="relative">
                    <input type="text" name="keyword" placeholder="搜索频道..." 
                        class="pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="submit" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </form>
                <!-- User Menu -->
                <div class="relative">
                    <button class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>
    
    <!-- Player -->
    @include('player.footer')
    
    <script src="{{ asset('js/hls.min.js') }}"></script>
    <script src="{{ asset('js/DPlayer.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @yield('scripts')
</body>
</html>