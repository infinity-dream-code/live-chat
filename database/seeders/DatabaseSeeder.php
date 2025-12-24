<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users if they don't exist
        $users = [
            ['name' => 'Delkira', 'username' => 'delkira', 'email' => 'delkira@example.com'],
            ['name' => 'Iruma', 'username' => 'iruma', 'email' => 'iruma@example.com'],
            ['name' => 'Ameri', 'username' => 'ameri', 'email' => 'ameri@example.com'],
            ['name' => 'asmodeus', 'username' => 'asmodeus', 'email' => 'asmodeus@example.com'],
            ['name' => 'clara', 'username' => 'clara', 'email' => 'clara@example.com'],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['username' => $userData['username']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('123'),
                ]
            );
        }

        // Create default group "Grup Anime" if it doesn't exist
        Group::firstOrCreate(
            ['name' => 'Grup Anime'],
            ['description' => 'Grup chat untuk semua member']
        );
    }
}
