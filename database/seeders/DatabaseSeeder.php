<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Главный сидер базы данных.
 *
 * Вызывает все необходимые сидеры при выполнении php artisan db:seed.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Запустить все сидеры.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
