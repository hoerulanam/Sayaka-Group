<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat user untuk testing
        User::updateOrCreate(
            ['email' => 'admin@sayaka.com'],
            [
                'name' => 'Admin SAYAKA',
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'editor@sayaka.com'],
            [
                'name' => 'Editor',
                'username' => 'editor',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_admin' => false,
            ]
        );
    }
}
