<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Organization\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика организаций для тестов.
 *
 * Генерирует случайные данные организаций.
 */
class OrganizationFactory extends Factory
{
    /**
     * Имя модели, для которой создана фабрика.
     *
     * @var class-string<Organization>
     */
    protected $model = Organization::class;

    /**
     * Определить состояние модели по умолчанию.
     *
     * @return array<string, mixed> Атрибуты модели
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'yandex_url' => 'https://yandex.ru/maps/org/' . $this->faker->numerify('########'),
            'yandex_place_id' => $this->faker->numerify('#####'),
            'rating' => $this->faker->randomFloat(2, 1, 5),
            'rating_count' => $this->faker->numberBetween(10, 500),
            'review_count' => $this->faker->numberBetween(5, 300),
            'status' => 'pending',
        ];
    }
}
