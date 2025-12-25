<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Channel;
use App\Models\SearchHistory;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Display search results.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $categoryId = $request->input('category', null);
        
        // Update search history if keyword is not empty
        if (!empty($keyword)) {
            SearchHistory::updateSearchHistory($keyword);
        }
        
        // Get popular search keywords
        $popularKeywords = SearchHistory::getPopularKeywords();
        
        // Get all categories for filtering
        $categories = Category::where('status', true)
            ->orderBy('name')
            ->get();
        
        // Build search query
        $query = Channel::where('is_active', true)
            ->where('is_approved', true);
        
        // Apply keyword filter
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }
        
        // Apply category filter
        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }
        
        // Get search results
        $channels = $query->orderBy('title')
            ->paginate(12);
        
        return view('search.index', compact(
            'keyword',
            'categoryId',
            'channels',
            'popularKeywords',
            'categories'
        ));
    }
}
