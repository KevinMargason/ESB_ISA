<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name' => 'Admin Local',
                'role' => 'admin',
                'password' => 'password123',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'supplier@local.test'],
            [
                'name' => 'Supplier Local',
                'role' => 'supplier',
                'password' => 'password123',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'kurir@local.test'],
            [
                'name' => 'Kurir Local',
                'role' => 'kurir',
                'password' => 'password123',
            ]
        );
    }
}
