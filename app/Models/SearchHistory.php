<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'search_history';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'keyword',
        'search_count',
    ];
    
    /**
     * Get popular search keywords.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPopularKeywords($limit = 10)
    {
        return self::orderBy('search_count', 'desc')
            ->limit($limit)
            ->pluck('keyword');
    }
    
    /**
     * Update or create search history.
     *
     * @param string $keyword
     * @return void
     */
    public static function updateSearchHistory($keyword)
    {
        self::updateOrCreate(
            ['keyword' => $keyword],
            [
                'search_count' => \DB::raw('search_count + 1'),
                'updated_at' => now(),
            ]
        );
    }
}
