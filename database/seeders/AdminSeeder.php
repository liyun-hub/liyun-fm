<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create default admin account
        Admin::create([
            'name' => '管理员',
            'email' => 'admin@fm.liy.ink',
            'password' => bcrypt(env('ADMIN_DEFAULT_PASSWORD')), // Default password from environment variable
        ]);
    }
}
