<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display all categories with their children.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $categories = Category::where('parent_id', null)
            ->where('status', true)
            ->with('children')
            ->orderBy('order')
            ->get();
        
        return view('categories.index', compact('categories'));
    }
    
    /**
     * Display channels by category.
     *
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function show($slug)
    {
        $category = Category::where('slug', $slug)
            ->where('status', true)
            ->firstOrFail();
        
        $channels = $category->channels()
            ->where('is_active', true)
            ->where('is_approved', true)
            ->orderBy('order')
            ->paginate(12);
        
        $parentCategories = Category::where('parent_id', null)
            ->where('status', true)
            ->with('children')
            ->orderBy('order')
            ->get();
        
        return view('categories.show', compact('category', 'channels', 'parentCategories'));
    }
}
