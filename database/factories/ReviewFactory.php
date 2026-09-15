<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Organization\Models\Organization;
use App\Domain\Review\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика отзывов для тестов.
 *
 * Генерирует случайные данные отзывов.
 */
class ReviewFactory extends Factory
{
    /**
     * Имя модели, для которой создана фабрика.
     *
     * @var class-string<Review>
     */
    protected $model = Review::class;

    /**
     * Определить состояние модели по умолчанию.
     *
     * @return array<string, mixed> Атрибуты модели
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'external_id' => $this->faker->uuid(),
            'author_name' => $this->faker->name(),
            'author_avatar_url' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'text' => $this->faker->paragraph(),
            'published_at' => $this->faker->dateTimeBetween('-1 year'),
        ];
    }
}
