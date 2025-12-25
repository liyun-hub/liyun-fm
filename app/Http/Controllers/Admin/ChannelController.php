<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $channels = Channel::with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('admin.channels.index', compact('channels'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = Category::where('status', true)
            ->orderBy('name')
            ->get();
        
        return view('admin.channels.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'required|string',
            'stream_url' => 'required|url',
            'category_id' => 'required|exists:categories,id',
            'logo_url' => 'nullable|url',
            'country' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
        ]);
        
        $channel = new Channel();
        $channel->title = $request->title;
        $channel->slug = Str::slug($request->title);
        $channel->description = $request->description;
        $channel->stream_url = $request->stream_url;
        $channel->logo_url = $request->logo_url;
        $channel->category_id = $request->category_id;
        $channel->country = $request->country;
        $channel->language = $request->language;
        $channel->is_active = $request->has('is_active');
        $channel->is_approved = $request->has('is_approved');
        $channel->proxy_enabled = $request->has('proxy_enabled');
        $channel->save();
        
        return redirect()->route('admin.channels.index')
            ->with('success', '频道创建成功');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $channel = Channel::with('category')->findOrFail($id);
        
        return view('admin.channels.show', compact('channel'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $channel = Channel::findOrFail($id);
        $categories = Category::where('status', true)
            ->orderBy('name')
            ->get();
        
        return view('admin.channels.edit', compact('channel', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'required|string',
            'stream_url' => 'required|url',
            'category_id' => 'required|exists:categories,id',
            'logo_url' => 'nullable|url',
            'country' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
        ]);
        
        $channel->title = $request->title;
        $channel->slug = Str::slug($request->title);
        $channel->description = $request->description;
        $channel->stream_url = $request->stream_url;
        $channel->logo_url = $request->logo_url;
        $channel->category_id = $request->category_id;
        $channel->country = $request->country;
        $channel->language = $request->language;
        $channel->is_active = $request->has('is_active');
        $channel->is_approved = $request->has('is_approved');
        $channel->proxy_enabled = $request->has('proxy_enabled');
        $channel->save();
        
        return redirect()->route('admin.channels.index')
            ->with('success', '频道更新成功');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $channel = Channel::findOrFail($id);
        $channel->delete();
        
        return redirect()->route('admin.channels.index')
            ->with('success', '频道删除成功');
    }
    
    /**
     * Approve a channel.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $channel = Channel::findOrFail($id);
        $channel->is_approved = true;
        $channel->save();
        
        return redirect()->route('admin.channels.index')
            ->with('success', '频道已批准');
    }
    
    /**
     * Toggle channel active status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus($id)
    {
        $channel = Channel::findOrFail($id);
        $channel->is_active = !$channel->is_active;
        $channel->save();
        
        $status = $channel->is_active ? '激活' : '停用';
        return redirect()->route('admin.channels.index')
            ->with('success', "频道已{$status}");
    }
}
