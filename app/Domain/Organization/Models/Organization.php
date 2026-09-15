<?php

declare(strict_types=1);

namespace App\Domain\Organization\Models;

use App\Domain\Parsing\Models\ParsingJob;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель организации.
 *
 * Хранит данные об организации, добавленной пользователем:
 * ссылку на Яндекс.Карты, метрики (рейтинг, оценки, отзывы),
 * статус парсинга и последнюю ошибку.
 *
 * @property int $id Идентификатор организации
 * @property int $user_id ID пользователя-владельца
 * @property string|null $name Название организации
 * @property string $yandex_url Ссылка на карточку Яндекс.Карт
 * @property string|null $yandex_place_id Идентификатор места
 * @property float|null $rating Средний рейтинг (от 1.00 до 5.00)
 * @property int $rating_count Количество оценок
 * @property int $review_count Количество отзывов
 * @property string $status Статус парсинга
 * @property string|null $last_error Текст последней ошибки
 * @property \Carbon\Carbon|null $last_parsed_at Дата последнего парсинга
 */
class Organization extends Model
{
    use HasFactory;

    /**
     * Атрибуты, доступные для массового присваивания.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id', 'name', 'yandex_url', 'yandex_place_id',
        'rating', 'rating_count', 'review_count', 'status',
        'last_error', 'last_parsed_at',
    ];

    /**
     * Приведение типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'rating_count' => 'integer',
            'review_count' => 'integer',
            'last_parsed_at' => 'datetime',
        ];
    }

    /**
     * Связь с пользователем-владельцем.
     *
     * @return BelongsTo Связь "принадлежит пользователю"
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с отзывами организации.
     *
     * @return HasMany Связь "имеет много отзывов"
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Связь со снимками метрик.
     *
     * @return HasMany Связь "имеет много снимков"
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(ReviewSnapshot::class);
    }

    /**
     * Связь с заданиями парсинга.
     *
     * @return HasMany Связь "имеет много заданий"
     */
    public function parsingJobs(): HasMany
    {
        return $this->hasMany(ParsingJob::class);
    }

    /**
     * Получить последнее задание парсинга.
     *
     * @return ParsingJob|null
     */
    public function latestJob(): ?ParsingJob
    {
        return $this->parsingJobs()->latest()->first();
    }

    /**
     * Проверить, требует ли организация внимания.
     *
     * @return bool true если статус = 'requires_attention'
     */
    public function requiresAttention(): bool
    {
        return $this->status === 'requires_attention';
    }

    /**
     * Фабрика для модели.
     *
     * @return \Database\Factories\OrganizationFactory
     */
    protected static function newFactory(): \Database\Factories\OrganizationFactory
    {
        return \Database\Factories\OrganizationFactory::new();
    }
}
