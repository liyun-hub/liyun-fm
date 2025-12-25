<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => '音乐',
                'slug' => 'music',
                'description' => '各种音乐频道',
                'status' => true,
                'parent_id' => null,
            ],
            [
                'name' => '新闻',
                'slug' => 'news',
                'description' => '新闻资讯频道',
                'status' => true,
                'parent_id' => null,
            ],
            [
                'name' => '体育',
                'slug' => 'sports',
                'description' => '体育赛事频道',
                'status' => true,
                'parent_id' => null,
            ],
            [
                'name' => '娱乐',
                'slug' => 'entertainment',
                'description' => '娱乐综艺频道',
                'status' => true,
                'parent_id' => null,
            ],
            [
                'name' => '电影',
                'slug' => 'movies',
                'description' => '电影频道',
                'status' => true,
                'parent_id' => null,
            ],
            [
                'name' => '纪录片',
                'slug' => 'documentary',
                'description' => '纪录片频道',
                'status' => true,
                'parent_id' => null,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}