<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Category;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the home page with featured channels and categories.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get featured channels (active and approved)
        $featuredChannels = Channel::where('is_active', true)
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get();
        
        // Get all categories for navigation
        $categories = Category::where('status', true)
            ->orderBy('name')
            ->get();
        
        // Get parent categories for the categories section
        $parentCategories = Category::where('parent_id', null)
            ->where('status', true)
            ->orderBy('name')
            ->take(6)
            ->get();
        
        return view('welcome', compact('featuredChannels', 'categories', 'parentCategories'));
    }
}
