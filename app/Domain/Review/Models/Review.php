<?php

declare(strict_types=1);

namespace App\Domain\Review\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Модель отзыва.
 *
 * Хранит данные одного отзыва: автор, рейтинг, текст, дата публикации.
 * Уникальный ключ (organization_id, external_id) обеспечивает идемпотентность —
 * при повторном парсинге дубли не создаются, а существующие обновляются.
 *
 * @property int $id Идентификатор отзыва
 * @property int $organization_id ID организации
 * @property string $external_id Внешний идентификатор отзыва (от Яндекса или сгенерированный)
 * @property string $author_name Имя автора отзыва
 * @property string|null $author_avatar_url URL аватара автора
 * @property int $rating Оценка от 1 до 5
 * @property string|null $text Текст отзыва
 * @property \Carbon\Carbon|null $published_at Дата публикации отзыва
 * @property \Carbon\Carbon|null $created_at Дата создания записи
 * @property \Carbon\Carbon|null $updated_at Дата обновления записи
 *
 * @package App\Domain\Review\Models
 */
class Review extends Model
{
    use HasFactory;

    /**
     * Атрибуты, доступные для массового присваивания.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'external_id',
        'author_name',
        'author_avatar_url',
        'rating',
        'text',
        'published_at',
    ];

    /**
     * Приведение типов атрибутов.
     *
     * @return array<string, string> Маппинг атрибут → тип
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Связь с организацией.
     *
     * @return BelongsTo Связь "принадлежит организации"
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Фабрика для модели.
     *
     * @return \Database\Factories\ReviewFactory
     */
    protected static function newFactory(): \Database\Factories\ReviewFactory
    {
        return \Database\Factories\ReviewFactory::new();
    }
}
