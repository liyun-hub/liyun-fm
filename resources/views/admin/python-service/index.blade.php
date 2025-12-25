@extends('admin.layouts.app')

@section('title', 'Python服务管理')

@section('page-title', 'Python服务管理')

@section('styles')
<style>
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .status-running {
        background-color: #10b981;
    }
    .status-stopped {
        background-color: #ef4444;
    }
    .status-warning {
        background-color: #f59e0b;
    }
    .action-btn {
        margin-right: 5px;
    }
    .toolbar {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }
    .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }
    .btn-danger {
        background-color: #ef4444;
        color: white;
        border: 1px solid #ef4444;
    }
    .btn-danger:hover {
        background-color: #dc2626;
        border-color: #dc2626;
    }
    .btn-warning {
        background-color: #f59e0b;
        color: white;
        border: 1px solid #f59e0b;
    }
    .btn-warning:hover {
        background-color: #d97706;
        border-color: #d97706;
    }
    .btn-info {
        background-color: #3b82f6;
        color: white;
        border: 1px solid #3b82f6;
    }
    .btn-info:hover {
        background-color: #2563eb;
        border-color: #2563eb;
    }
    .btn-success {
        background-color: #10b981;
        color: white;
        border: 1px solid #10b981;
    }
    .btn-success:hover {
        background-color: #059669;
        border-color: #059669;
    }
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .service-card {
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .service-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .service-status-text {
        font-size: 1.125rem;
        font-weight: 600;
    }
    .process-info {
        background-color: #ffffff;
        border-radius: 0.375rem;
        padding: 1rem;
        margin: 0.5rem 0;
        border: 1px solid #e5e7eb;
    }
    .process-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    .process-value {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
    }
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .toggle-slider {
        background-color: #10b981;
    }
    input:focus + .toggle-slider {
        box-shadow: 0 0 1px #10b981;
    }
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }
</style>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <p class="text-gray-600">管理Python音频服务和FFmpeg进程</p>
    </div>

    <!-- Python服务状态卡片 -->
    <div class="service-card">
        <h3 class="text-lg font-semibold mb-4">Python服务状态</h3>
        
        <div class="service-status">
            <span class="status-indicator {{ $serviceStatus['is_running'] ? 'status-running' : 'status-stopped' }}"></span>
            <span class="service-status-text {{ $serviceStatus['is_running'] ? 'text-green-700' : 'text-red-700' }}">
                {{ $serviceStatus['is_running'] ? '运行中' : '已停止' }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="process-info">
                <div class="process-label">服务URL</div>
                <div class="process-value">{{ $serviceStatus['service_url'] }}</div>
            </div>
            <div class="process-info">
                <div class="process-label">进程ID</div>
                <div class="process-value">{{ $serviceStatus['pid'] ?? 'N/A' }}</div>
            </div>
            <div class="process-info">
                <div class="process-label">启动时间</div>
                <div class="process-value">{{ $serviceStatus['start_time'] ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="toolbar">
            @if($serviceStatus['is_running'])
                <button id="stopServiceBtn" class="btn btn-danger action-btn">停止服务</button>
                <button id="restartServiceBtn" class="btn btn-warning action-btn">重启服务</button>
            @else
                <button id="startServiceBtn" class="btn btn-success action-btn">启动服务</button>
            @endif
            <button id="refreshStatusBtn" class="btn btn-info action-btn">刷新状态</button>
            <button id="autoFixBtn" class="btn btn-info action-btn">自动修复</button>
        </div>

        <!-- 监控设置 -->
        <div class="mt-4 p-4 bg-white rounded-lg border border-gray-200">
            <h4 class="font-medium mb-2">监控设置</h4>
            <div class="flex items-center justify-between">
                <label for="monitorToggle" class="text-sm font-medium text-gray-700">启用自动监控</label>
                <label class="toggle-switch">
                    <input type="checkbox" id="monitorToggle" {{ $serviceStatus['monitor_enabled'] ? 'checked' : '' }}>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>

    <!-- FFmpeg进程列表 -->
    <div class="service-card">
        <h3 class="text-lg font-semibold mb-4">FFmpeg进程列表</h3>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">进程ID</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">频道ID</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">流地址</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">状态</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">启动时间</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">操作</th>
                    </tr>
                </thead>
                <tbody id="ffmpegProcessTableBody">
                    @foreach($ffmpegProcesses as $process)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-sm text-gray-800">{{ $process['pid'] }}</td>
                        <td class="py-3 px-4 text-sm text-gray-800">{{ $process['channel_id'] }}</td>
                        <td class="py-3 px-4 text-sm text-gray-600"><small>{{ $process['stream_url'] }}</small></td>
                        <td class="py-3 px-4">
                            <span class="status-indicator status-running"></span>
                            <span class="text-sm text-green-700">运行中</span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">{{ $process['start_time'] }}</td>
                        <td class="py-3 px-4">
                            <button class="btn btn-sm btn-danger action-btn kill-ffmpeg-btn" data-pid="{{ $process['pid'] }}">终止</button>
                        </td>
                    </tr>
                    @endforeach
                    @if(empty($ffmpegProcesses))
                    <tr>
                        <td colspan="6" class="py-6 px-4 text-center text-sm text-gray-500">
                            暂无运行中的FFmpeg进程
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 启动服务
        document.getElementById('startServiceBtn')?.addEventListener('click', function() {
            const btnText = this.innerHTML;
            this.innerHTML = '处理中...';
            this.disabled = true;
            
            axios.post('/admin/python-service/start')
                .then(response => {
                    alert(response.data.message);
                    refreshStatus();
                })
                .catch(error => {
                    const message = error.response?.data?.message || '操作失败';
                    alert(message);
                })
                .finally(() => {
                    this.innerHTML = btnText;
                    this.disabled = false;
                });
        });

        // 停止服务
        document.getElementById('stopServiceBtn')?.addEventListener('click', function() {
            if (!confirm('确定要停止Python服务吗？')) {
                return;
            }
            
            const btnText = this.innerHTML;
            this.innerHTML = '处理中...';
            this.disabled = true;
            
            axios.post('/admin/python-service/stop')
                .then(response => {
                    alert(response.data.message);
                    refreshStatus();
                })
                .catch(error => {
                    const message = error.response?.data?.message || '操作失败';
                    alert(message);
                })
                .finally(() => {
                    this.innerHTML = btnText;
                    this.disabled = false;
                });
        });

        // 重启服务
        document.getElementById('restartServiceBtn')?.addEventListener('click', function() {
            if (!confirm('确定要重启Python服务吗？')) {
                return;
            }
            
            const btnText = this.innerHTML;
            this.innerHTML = '处理中...';
            this.disabled = true;
            
            axios.post('/admin/python-service/restart')
                .then(response => {
                    alert(response.data.message);
                    refreshStatus();
                })
                .catch(error => {
                    const message = error.response?.data?.message || '操作失败';
                    alert(message);
                })
                .finally(() => {
                    this.innerHTML = btnText;
                    this.disabled = false;
                });
        });

        // 刷新状态
        document.getElementById('refreshStatusBtn').addEventListener('click', refreshStatus);

        // 自动修复
        document.getElementById('autoFixBtn').addEventListener('click', function() {
            const btnText = this.innerHTML;
            this.innerHTML = '处理中...';
            this.disabled = true;
            
            axios.post('/admin/python-service/auto-fix')
                .then(response => {
                    alert(response.data.message);
                    refreshStatus();
                })
                .catch(error => {
                    const message = error.response?.data?.message || '操作失败';
                    alert(message);
                })
                .finally(() => {
                    this.innerHTML = btnText;
                    this.disabled = false;
                });
        });

        // 终止FFmpeg进程
        document.querySelectorAll('.kill-ffmpeg-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const pid = this.dataset.pid;
                if (!confirm(`确定要终止FFmpeg进程 ${pid} 吗？`)) {
                    return;
                }
                
                const btnText = this.innerHTML;
                this.innerHTML = '处理中...';
                this.disabled = true;
                
                axios.post(`/admin/python-service/ffmpeg/${pid}/kill`)
                    .then(response => {
                        alert(response.data.message);
                        refreshStatus();
                    })
                    .catch(error => {
                        const message = error.response?.data?.message || '操作失败';
                        alert(message);
                    })
                    .finally(() => {
                        this.innerHTML = btnText;
                        this.disabled = false;
                    });
            });
        });

        // 监控开关
        document.getElementById('monitorToggle').addEventListener('change', function() {
            const enabled = this.checked;
            
            axios.post('/admin/python-service/monitor', { enabled: enabled })
                .then(response => {
                    alert(response.data.message);
                })
                .catch(error => {
                    const message = error.response?.data?.message || '操作失败';
                    alert(message);
                    // 恢复原状态
                    this.checked = !enabled;
                });
        });

        // 自动刷新（每10秒）
        // setInterval(refreshStatus, 10000);
    });

    // 刷新状态
    function refreshStatus() {
        axios.get('/admin/python-service/status')
            .then(response => {
                if (response.data.code === 200) {
                    window.location.reload(); // 简单起见，直接刷新页面
                }
            })
            .catch(error => {
                console.error('刷新状态失败:', error);
            });
    }
</script>
@endsection

@endsection