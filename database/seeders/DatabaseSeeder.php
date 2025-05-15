<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpassword123'),
            'role' => 'admin', // Đảm bảo cột role có sẵn trong bảng users
            'id_cooklab' => 'cook_' . Str::random(10),
        ]);

        // Tạo User mẫu
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user', // Đảm bảo cột role có sẵn trong bảng users
        ]);

        // Tạo Admin mẫu
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpassword123'),
            'role' => 'admin', // Đảm bảo cột role có sẵn trong bảng users
            'id_cooklab' => 'cook_' . Str::random(10),
        ]);
    }
}
