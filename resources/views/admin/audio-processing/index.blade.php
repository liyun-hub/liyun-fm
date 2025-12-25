@extends('admin.layouts.app')

@section('title', '音频处理服务管理')

@section('page-title', '音频处理服务管理')

@section('styles')
<style>
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .status-running { background-color: #10b981; }
    .status-stopped { background-color: #ef4444; }
    .status-error { background-color: #f59e0b; }
    .status-starting { background-color: #3b82f6; }
    
    .service-card {
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .metric-card {
        background-color: #ffffff;
        border-radius: 0.375rem;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        text-align: center;
    }
    
    .metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #111827;
    }
    
    .metric-label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    
    .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-weight: 500;
        transition: all 0.15s ease;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .btn-primary { background-color: #3b82f6; color: white; border: 1px solid #3b82f6; }
    .btn-primary:hover { background-color: #2563eb; }
    
    .btn-success { background-color: #10b981; color: white; border: 1px solid #10b981; }
    .btn-success:hover { background-color: #059669; }
    
    .btn-danger { background-color: #ef4444; color: white; border: 1px solid #ef4444; }
    .btn-danger:hover { background-color: #dc2626; }
    
    .btn-warning { background-color: #f59e0b; color: white; border: 1px solid #f59e0b; }
    .btn-warning:hover { background-color: #d97706; }
    
    .btn-sm { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
    
    .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .alert-danger {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }
    
    .alert-success {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }
    
    .process-table {
        background-color: white;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    
    .process-table th {
        background-color: #f9fafb;
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .process-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .process-table tr:hover {
        background-color: #f9fafb;
    }
    
    .error-log {
        background-color: #1f2937;
        color: #f9fafb;
        padding: 1rem;
        border-radius: 0.375rem;
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        max-height: 300px;
        overflow-y: auto;
    }
</style>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    @if(isset($error))
        <div class="alert alert-danger">
            <strong>错误:</strong> {{ $error }}
        </div>
    @endif

    <!-- Python服务状态卡片 -->
    <div class="service-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Python服务状态</h3>
            <button id="refreshPythonBtn" class="btn btn-primary">刷新</button>
        </div>
        
        @if($python_service_info)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="metric-card">
                <div class="metric-value {{ $python_service_info['running'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $python_service_info['running'] ? '运行中' : '已停止' }}
                </div>
                <div class="metric-label">服务状态</div>
            </div>
            
            @if($python_service_info['running'])
            <div class="metric-card">
                <div class="metric-value text-blue-600">{{ $python_service_info['pid'] }}</div>
                <div class="metric-label">进程ID</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-value text-purple-600">{{ $python_service_info['cpu_usage'] }}%</div>
                <div class="metric-label">CPU使用率</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-value text-orange-600">{{ $python_service_info['memory_usage'] }}%</div>
                <div class="metric-label">内存使用率</div>
            </div>
            @else
            <div class="metric-card">
                <div class="metric-value text-gray-400">--</div>
                <div class="metric-label">进程ID</div>
            </div>
            <div class="metric-card">
                <div class="metric-value text-gray-400">--</div>
                <div class="metric-label">CPU使用率</div>
            </div>
            <div class="metric-card">
                <div class="metric-value text-gray-400">--</div>
                <div class="metric-label">内存使用率</div>
            </div>
            @endif
        </div>

        @if($python_service_info['running'] && $python_service_info['start_time'])
        <div class="mb-4">
            <p class="text-sm text-gray-600">
                <strong>启动时间:</strong> {{ $python_service_info['start_time'] }}
            </p>
        </div>
        @endif

        @if(isset($python_service_info['error']))
        <div class="mb-4 p-3 bg-red-100 border border-red-300 rounded text-red-700">
            <strong>错误:</strong> {{ $python_service_info['error'] }}
        </div>
        @endif

        <!-- Python服务管理操作 -->
        <div class="border-t pt-4">
            <h4 class="font-medium mb-3">Python服务管理</h4>
            <div class="flex flex-wrap">
                @if($python_service_info['running'])
                    <button id="stopPythonBtn" class="btn btn-danger">停止服务</button>
                    <button id="restartPythonBtn" class="btn btn-warning">重启服务</button>
                @else
                    <button id="startPythonBtn" class="btn btn-success">启动服务</button>
                @endif
            </div>
        </div>
        @else
        <div class="text-center py-4 text-gray-500">
            <p>无法获取Python服务信息</p>
            <button id="startPythonBtn" class="btn btn-success mt-2">尝试启动服务</button>
        </div>
        @endif
    </div>

    <!-- 服务状态概览 -->
    <div class="service-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">服务状态概览</h3>
            <button id="refreshBtn" class="btn btn-primary">刷新状态</button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="metric-card">
                <div class="metric-value {{ $service_available ? 'text-green-600' : 'text-red-600' }}">
                    {{ $service_available ? '在线' : '离线' }}
                </div>
                <div class="metric-label">服务状态</div>
            </div>
            
            @if($service_status)
            <div class="metric-card">
                <div class="metric-value text-blue-600">{{ $service_status['services']['process_manager']['active_processes'] ?? 0 }}</div>
                <div class="metric-label">活跃进程</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-value text-purple-600">{{ number_format($service_status['system_health']['system_resources']['cpu_percent'] ?? 0, 1) }}%</div>
                <div class="metric-label">CPU使用率</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-value text-orange-600">{{ number_format($service_status['system_health']['system_resources']['memory_percent'] ?? 0, 1) }}%</div>
                <div class="metric-label">内存使用率</div>
            </div>
            @else
            <div class="metric-card">
                <div class="metric-value text-gray-400">--</div>
                <div class="metric-label">活跃进程</div>
            </div>
            <div class="metric-card">
                <div class="metric-value text-gray-400">--</div>
                <div class="metric-label">CPU使用率</div>
            </div>
            <div class="metric-card">
                <div class="metric-value text-gray-400">--</div>
                <div class="metric-label">内存使用率</div>
            </div>
            @endif
        </div>

        @if($health_check)
        <div class="mb-4">
            <h4 class="font-medium mb-2">系统健康状态: 
                <span class="px-2 py-1 rounded text-sm {{ $health_check['overall_status'] === 'healthy' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $health_check['overall_status'] === 'healthy' ? '健康' : '异常' }}
                </span>
            </h4>
            @if(!empty($health_check['issues']))
                <div class="text-sm text-red-600">
                    <strong>问题:</strong>
                    <ul class="list-disc list-inside mt-1">
                        @foreach($health_check['issues'] as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        @endif

        <!-- 管理操作 -->
        <div class="border-t pt-4">
            <h4 class="font-medium mb-3">管理操作</h4>
            <div class="flex flex-wrap">
                @if($processes_data['total'] > 0)
                    <button id="stopAllBtn" class="btn btn-danger">停止所有进程</button>
                @endif
                <button id="cleanupBtn" class="btn btn-warning">强制清理</button>
                <button id="viewErrorsBtn" class="btn btn-primary">查看错误日志</button>
            </div>
        </div>
    </div>

    <!-- 进程列表 -->
    <div class="service-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">音频处理进程 ({{ $processes_data['total'] }})</h3>
        </div>

        @if($processes_data['total'] > 0)
            <div class="process-table">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th>频道ID</th>
                            <th>频道名称</th>
                            <th>进程ID</th>
                            <th>状态</th>
                            <th>启动时间</th>
                            <th>最后活动</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($processes_data['processes'] as $process)
                        <tr>
                            <td>{{ $process['channel_id'] }}</td>
                            <td>
                                @if(isset($channels[$process['channel_id']]))
                                    {{ $channels[$process['channel_id']]->title }}
                                @else
                                    未知频道
                                @endif
                            </td>
                            <td>{{ $process['pid'] }}</td>
                            <td>
                                <span class="status-indicator status-{{ $process['status'] }}"></span>
                                <span class="text-sm {{ $process['status'] === 'running' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $process['status'] === 'running' ? '运行中' : $process['status'] }}
                                </span>
                            </td>
                            <td class="text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($process['start_time'])->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($process['last_activity_time'])->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger stop-process-btn" data-channel-id="{{ $process['channel_id'] }}">
                                    停止
                                </button>
                                <button class="btn btn-sm btn-primary view-logs-btn" data-channel-id="{{ $process['channel_id'] }}">
                                    日志
                                </button>
                                @if($process['status'] === 'error')
                                    <button class="btn btn-sm btn-warning recovery-btn" data-channel-id="{{ $process['channel_id'] }}">
                                        恢复
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p>暂无运行中的音频处理进程</p>
            </div>
        @endif
    </div>

    <!-- 错误历史 -->
    @if($error_history && $error_history['statistics']['total_errors'] > 0)
    <div class="service-card">
        <h3 class="text-lg font-semibold mb-4">错误历史</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="metric-card">
                <div class="metric-value text-red-600">{{ $error_history['statistics']['total_errors'] }}</div>
                <div class="metric-label">总错误数</div>
            </div>
            <div class="metric-card">
                <div class="metric-value text-orange-600">{{ $error_history['statistics']['recent_errors'] }}</div>
                <div class="metric-label">最近错误数</div>
            </div>
            <div class="metric-card">
                <div class="metric-value text-green-600">{{ number_format($error_history['statistics']['recovery_rate'] * 100, 1) }}%</div>
                <div class="metric-label">恢复成功率</div>
            </div>
        </div>

        @if(!empty($error_history['recent_errors']))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">时间</th>
                            <th class="text-left py-2">频道</th>
                            <th class="text-left py-2">错误类型</th>
                            <th class="text-left py-2">错误信息</th>
                            <th class="text-left py-2">恢复状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($error_history['recent_errors'], 0, 10) as $error)
                        <tr class="border-b border-gray-100">
                            <td class="py-2">{{ \Carbon\Carbon::parse($error['timestamp'])->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s') }}</td>
                            <td class="py-2">{{ $error['channel_id'] }}</td>
                            <td class="py-2">{{ $error['error_type'] }}</td>
                            <td class="py-2 max-w-xs truncate">{{ $error['error_message'] }}</td>
                            <td class="py-2">
                                @if($error['recovery_attempted'])
                                    <span class="text-{{ $error['recovery_successful'] ? 'green' : 'red' }}-600">
                                        {{ $error['recovery_successful'] ? '成功' : '失败' }}
                                    </span>
                                @else
                                    <span class="text-gray-500">未尝试</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endif
</div>

<!-- 模态框 -->
<div id="logModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-96">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold">进程日志</h3>
                <button id="closeLogModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <div id="logContent" class="error-log">
                    加载中...
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 刷新状态
    document.getElementById('refreshBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    // 刷新Python服务状态
    document.getElementById('refreshPythonBtn')?.addEventListener('click', function() {
        window.location.reload();
    });

    // 启动Python服务
    document.getElementById('startPythonBtn')?.addEventListener('click', function() {
        if (!confirm('确定要启动Python服务吗？')) return;
        
        const btn = this;
        const originalText = btn.textContent;
        btn.textContent = '启动中...';
        btn.disabled = true;
        
        fetch('/admin/audio-processing/python/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            alert('操作失败: ' + error.message);
        })
        .finally(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });

    // 停止Python服务
    document.getElementById('stopPythonBtn')?.addEventListener('click', function() {
        if (!confirm('确定要停止Python服务吗？这将停止所有音频处理进程！')) return;
        
        const btn = this;
        const originalText = btn.textContent;
        btn.textContent = '停止中...';
        btn.disabled = true;
        
        fetch('/admin/audio-processing/python/stop', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            alert('操作失败: ' + error.message);
        })
        .finally(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });

    // 重启Python服务
    document.getElementById('restartPythonBtn')?.addEventListener('click', function() {
        if (!confirm('确定要重启Python服务吗？')) return;
        
        const btn = this;
        const originalText = btn.textContent;
        btn.textContent = '重启中...';
        btn.disabled = true;
        
        fetch('/admin/audio-processing/python/restart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            alert('操作失败: ' + error.message);
        })
        .finally(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });

    // 停止所有进程
    document.getElementById('stopAllBtn')?.addEventListener('click', function() {
        if (!confirm('确定要停止所有音频处理进程吗？')) return;
        
        const btn = this;
        const originalText = btn.textContent;
        btn.textContent = '处理中...';
        btn.disabled = true;
        
        fetch('/admin/audio-processing/stop-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            alert('操作失败: ' + error.message);
        })
        .finally(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });

    // 强制清理
    document.getElementById('cleanupBtn')?.addEventListener('click', function() {
        if (!confirm('确定要执行强制清理吗？')) return;
        
        const btn = this;
        const originalText = btn.textContent;
        btn.textContent = '处理中...';
        btn.disabled = true;
        
        fetch('/admin/audio-processing/cleanup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        })
        .catch(error => {
            alert('操作失败: ' + error.message);
        })
        .finally(() => {
            btn.textContent = originalText;
            btn.disabled = false;
        });
    });

    // 查看错误日志
    document.getElementById('viewErrorsBtn')?.addEventListener('click', function() {
        fetch('/admin/audio-processing/errors')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let errorText = '最近错误记录:\n\n';
                    if (data.data.recent_errors.length > 0) {
                        data.data.recent_errors.forEach(error => {
                            errorText += `时间: ${error.timestamp}\n`;
                            errorText += `频道: ${error.channel_id}\n`;
                            errorText += `类型: ${error.error_type}\n`;
                            errorText += `信息: ${error.error_message}\n`;
                            errorText += `恢复: ${error.recovery_successful ? '成功' : '失败'}\n\n`;
                        });
                    } else {
                        errorText += '暂无错误记录';
                    }
                    alert(errorText);
                } else {
                    alert('获取错误日志失败');
                }
            })
            .catch(error => {
                alert('获取错误日志失败: ' + error.message);
            });
    });

    // 停止单个进程
    document.querySelectorAll('.stop-process-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const channelId = this.dataset.channelId;
            if (!confirm(`确定要停止频道 ${channelId} 的进程吗？`)) return;
            
            const originalText = this.textContent;
            this.textContent = '处理中...';
            this.disabled = true;
            
            fetch(`/admin/audio-processing/${channelId}/stop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(error => {
                alert('操作失败: ' + error.message);
            })
            .finally(() => {
                this.textContent = originalText;
                this.disabled = false;
            });
        });
    });

    // 查看日志
    document.querySelectorAll('.view-logs-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const channelId = this.dataset.channelId;
            showProcessLogs(channelId);
        });
    });

    // 错误恢复
    document.querySelectorAll('.recovery-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const channelId = this.dataset.channelId;
            if (!confirm(`确定要尝试恢复频道 ${channelId} 吗？`)) return;
            
            const originalText = this.textContent;
            this.textContent = '处理中...';
            this.disabled = true;
            
            fetch(`/admin/audio-processing/${channelId}/recovery`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(error => {
                alert('操作失败: ' + error.message);
            })
            .finally(() => {
                this.textContent = originalText;
                this.disabled = false;
            });
        });
    });

    // 模态框控制
    document.getElementById('closeLogModal')?.addEventListener('click', function() {
        document.getElementById('logModal').classList.add('hidden');
    });
});

// 显示进程日志
function showProcessLogs(channelId) {
    const modal = document.getElementById('logModal');
    const content = document.getElementById('logContent');
    
    content.textContent = '加载中...';
    modal.classList.remove('hidden');
    
    fetch(`/admin/audio-processing/${channelId}/logs`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.logs) {
                content.textContent = data.data.logs.join('\n') || '暂无日志';
            } else {
                content.textContent = '获取日志失败';
            }
        })
        .catch(error => {
            content.textContent = '获取日志失败: ' + error.message;
        });
}
</script>
@endsection