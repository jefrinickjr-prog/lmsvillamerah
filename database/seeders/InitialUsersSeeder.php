<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'approved_at' => now(),
        ]);
        User::create(["name" => "Teacher", "email" => "teacher@example.com", "password" => Hash::make('password'), 'role' => 'teacher']);
        User::create(["name" => "Student", "email" => "student@example.com", "password" => Hash::make('password'), 'role' => 'student']);
    }
}
