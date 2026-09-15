<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Сидер тестового пользователя.
 */
class UserSeeder extends Seeder
{
    /**
     * Создать тестового пользователя.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            ['name' => 'Тестовый пользователь', 'password' => Hash::make('password')],
        );
    }
}
