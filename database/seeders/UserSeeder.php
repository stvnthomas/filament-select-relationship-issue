<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->create([
                'name' => 'User 1',
                'email' => 'user1@example.com',
            ]);

        User::factory()
            ->create([
                'name' => 'User 2',
                'email' => 'user2@example.com',
            ]);
    }
}
