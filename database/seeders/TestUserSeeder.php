<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds a test user for E2E testing
     */
    public function run(): void
    {
        // Create test user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password'),
                'status' => User::STATUS_ACTIVE,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create additional test users for different scenarios
        User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'status' => User::STATUS_ACTIVE,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
