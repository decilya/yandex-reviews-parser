<?php

declare(strict_types=1);

namespace App\Domain\Review\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Модель снимка метрик организации.
 *
 * Создаётся после каждого успешного парсинга. Позволяет отслеживать
 * историю изменений рейтинга и количества отзывов во времени.
 *
 * @property int $id Идентификатор снимка
 * @property int $organization_id ID организации
 * @property float $rating Средний рейтинг на момент снимка
 * @property int $rating_count Количество оценок на момент снимка
 * @property int $review_count Количество отзывов на момент снимка
 * @property int $reviews_fetched Количество отзывов, фактически полученных парсером
 * @property \Carbon\Carbon $snapshotted_at Дата создания снимка
 * @property \Carbon\Carbon|null $created_at Дата создания записи
 * @property \Carbon\Carbon|null $updated_at Дата обновления записи
 *
 * @package App\Domain\Review\Models
 */
class ReviewSnapshot extends Model
{
    use HasFactory;

    /**
     * Атрибуты, доступные для массового присваивания.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'rating',
        'rating_count',
        'review_count',
        'reviews_fetched',
        'snapshotted_at',
    ];

    /**
     * Приведение типов атрибутов.
     *
     * @return array<string, string> Маппинг атрибут → тип
     */
    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'rating_count' => 'integer',
            'review_count' => 'integer',
            'reviews_fetched' => 'integer',
            'snapshotted_at' => 'datetime',
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
}
