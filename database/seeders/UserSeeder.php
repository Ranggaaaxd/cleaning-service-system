<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cleaningservice.local'],
            [
                'name' => 'Admin',
                'password' => 'password123',
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'spv@cleaningservice.local'],
            [
                'name' => 'Supervisor',
                'password' => 'password123',
                'role' => 'supervisor',
                'is_active' => true,
            ],
        );
    }
}
