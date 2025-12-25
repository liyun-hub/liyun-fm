<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // 中间件已在路由中设置，这里不需要重复设置
    }
    
    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 检查管理员是否已登录
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }
        
        // Get dashboard statistics
        $totalChannels = Channel::count();
        $activeChannels = Channel::where('is_active', true)->count();
        $pendingChannels = Channel::where('is_approved', false)->count();
        $totalCategories = Category::count();
        
        // Get recent channels
        $recentChannels = Channel::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('admin.dashboard', compact(
            'totalChannels',
            'activeChannels',
            'pendingChannels',
            'totalCategories',
            'recentChannels'
        ));
    }
}
