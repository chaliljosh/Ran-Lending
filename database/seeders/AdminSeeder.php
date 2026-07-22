<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin One',
                'first_name' => 'Admin',
                'last_name' => 'One',
                'email' => 'admin1@ran.com',
                'password_hash' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'name' => 'Admin Two',
                'first_name' => 'Admin',
                'last_name' => 'Two',
                'email' => 'admin2@ran.com',
                'password_hash' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
            ],
            [
                'name' => 'Admin Three',
                'first_name' => 'Admin',
                'last_name' => 'Three',
                'email' => 'admin3@ran.com',
                'password_hash' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
            ],
        ]);
    }
}