<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| 
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
| 
*/

// Home page
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Categories routes
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// Search route
Route::get('/search', [SearchController::class, 'index'])->name('search');



// Stream proxy routes
Route::post('/stream/generate-play-id', [App\Http\Controllers\StreamProxyController::class, 'generatePlayId'])->name('stream.generate-play-id');
Route::get('/stream/proxy/{playId}', [App\Http\Controllers\StreamProxyController::class, 'proxy'])->name('stream.proxy');
Route::get('/stream/server-pool', [App\Http\Controllers\StreamProxyController::class, 'serverPool'])->name('stream.server-pool');

// API Routes for Playback
Route::prefix('api')->group(function () {
    Route::get('play/{channelId}', [App\Http\Controllers\Api\PlayController::class, 'play'])->name('api.play');
    Route::get('stream', [App\Http\Controllers\Api\PlayController::class, 'stream'])->name('api.play.stream');
    // Route for TS segments (HLS playback)
    Route::get('{segment}.ts', [App\Http\Controllers\Api\PlayController::class, 'streamTS'])->name('api.play.ts');
});



// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [LoginController::class, 'login'])->name('admin.login.submit');
    Route::post('logout', [LoginController::class, 'logout'])->name('admin.logout');
    
    // Admin Dashboard Routes - 使用简单的中间件检查
    Route::middleware(['web'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        
        // Channels Routes
        Route::resource('channels', ChannelController::class)->names([
            'index' => 'admin.channels.index',
            'create' => 'admin.channels.create',
            'store' => 'admin.channels.store',
            'show' => 'admin.channels.show',
            'edit' => 'admin.channels.edit',
            'update' => 'admin.channels.update',
            'destroy' => 'admin.channels.destroy',
        ]);
        
        Route::post('channels/{id}/approve', [ChannelController::class, 'approve'])->name('admin.channels.approve');
        Route::post('channels/{id}/toggle-status', [ChannelController::class, 'toggleStatus'])->name('admin.channels.toggle-status');
        
        // Categories Routes
        Route::resource('categories', AdminCategoryController::class)->names([
            'index' => 'admin.categories.index',
            'create' => 'admin.categories.create',
            'store' => 'admin.categories.store',
            'show' => 'admin.categories.show',
            'edit' => 'admin.categories.edit',
            'update' => 'admin.categories.update',
            'destroy' => 'admin.categories.destroy',
        ]);
        
        // Audio Processing Service Management Routes
        Route::prefix('audio-processing')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AudioProcessingController::class, 'index'])->name('admin.audio-processing.index');
            Route::get('status', [App\Http\Controllers\Admin\AudioProcessingController::class, 'getStatus'])->name('admin.audio-processing.status');
            Route::post('{channelId}/start', [App\Http\Controllers\Admin\AudioProcessingController::class, 'startProcess'])->name('admin.audio-processing.start');
            Route::post('{channelId}/stop', [App\Http\Controllers\Admin\AudioProcessingController::class, 'stopProcess'])->name('admin.audio-processing.stop');
            Route::get('{channelId}/status', [App\Http\Controllers\Admin\AudioProcessingController::class, 'getProcessStatus'])->name('admin.audio-processing.process-status');
            Route::get('{channelId}/logs', [App\Http\Controllers\Admin\AudioProcessingController::class, 'getProcessLogs'])->name('admin.audio-processing.logs');
            Route::post('stop-all', [App\Http\Controllers\Admin\AudioProcessingController::class, 'stopAllProcesses'])->name('admin.audio-processing.stop-all');
            
            // 新增管理功能路由
            Route::get('errors', [App\Http\Controllers\Admin\AudioProcessingController::class, 'getErrorHistory'])->name('admin.audio-processing.errors');
            Route::post('{channelId}/recovery', [App\Http\Controllers\Admin\AudioProcessingController::class, 'triggerRecovery'])->name('admin.audio-processing.recovery');
            Route::post('cleanup', [App\Http\Controllers\Admin\AudioProcessingController::class, 'forceCleanup'])->name('admin.audio-processing.cleanup');
            Route::post('{channelId}/activity', [App\Http\Controllers\Admin\AudioProcessingController::class, 'updateActivityTime'])->name('admin.audio-processing.activity');
            
            // Python服务管理路由
            Route::post('python/start', [App\Http\Controllers\Admin\AudioProcessingController::class, 'startPythonService'])->name('admin.audio-processing.python.start');
            Route::post('python/stop', [App\Http\Controllers\Admin\AudioProcessingController::class, 'stopPythonService'])->name('admin.audio-processing.python.stop');
            Route::post('python/restart', [App\Http\Controllers\Admin\AudioProcessingController::class, 'restartPythonService'])->name('admin.audio-processing.python.restart');
            Route::get('python/info', [App\Http\Controllers\Admin\AudioProcessingController::class, 'getPythonServiceInfo'])->name('admin.audio-processing.python.info');
        });
        
        // Python Service Management Routes
        Route::prefix('python-service')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\PythonServiceController::class, 'index'])->name('admin.python-service.index');
            Route::get('status', [App\Http\Controllers\Admin\PythonServiceController::class, 'getStatus'])->name('admin.python-service.status');
            Route::post('start', [App\Http\Controllers\Admin\PythonServiceController::class, 'startPython'])->name('admin.python-service.start');
            Route::post('stop', [App\Http\Controllers\Admin\PythonServiceController::class, 'stopPython'])->name('admin.python-service.stop');
            Route::post('restart', [App\Http\Controllers\Admin\PythonServiceController::class, 'restartPython'])->name('admin.python-service.restart');
            Route::get('ffmpeg', [App\Http\Controllers\Admin\PythonServiceController::class, 'getFFmpegStatus'])->name('admin.python-service.ffmpeg');
            Route::post('monitor', [App\Http\Controllers\Admin\PythonServiceController::class, 'monitor'])->name('admin.python-service.monitor');
            Route::post('ffmpeg/{pid}/kill', [App\Http\Controllers\Admin\PythonServiceController::class, 'killFFmpeg'])->name('admin.python-service.ffmpeg.kill');
            Route::post('auto-fix', [App\Http\Controllers\Admin\PythonServiceController::class, 'autoFix'])->name('admin.python-service.auto-fix');
        });
    });
});
