<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Модель задания парсинга.
 *
 * Отслеживает прогресс и состояние каждого задания парсинга.
 * Создаётся при запуске парсинга, обновляется по мере выполнения.
 *
 * @property int $id Идентификатор задания
 * @property int $organization_id ID организации
 * @property string $status Статус задания (queued/processing/completed/failed)
 * @property int $processed Количество обработанных отзывов
 * @property int $total Общее количество отзывов (ожидаемое)
 * @property string|null $error Текст ошибки, если задание провалилось
 * @property int $attempts Количество попыток выполнения
 * @property \Carbon\Carbon|null $created_at Дата создания записи
 * @property \Carbon\Carbon|null $updated_at Дата обновления записи
 *
 * @package App\Domain\Parsing\Models
 */
class ParsingJob extends Model
{
    use HasFactory;

    /**
     * Атрибуты, доступные для массового присваивания.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'status',
        'processed',
        'total',
        'error',
        'attempts',
    ];

    /**
     * Приведение типов атрибутов.
     *
     * @return array<string, string> Маппинг атрибут → тип
     */
    protected function casts(): array
    {
        return [
            'processed' => 'integer',
            'total' => 'integer',
            'attempts' => 'integer',
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
     * Проверить, завершено ли задание успешно.
     *
     * @return bool true если статус = 'completed'
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Проверить, провалилось ли задание.
     *
     * @return bool true если статус = 'failed'
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Вычислить процент выполнения задания.
     *
     * @return int Процент от 0 до 100
     */
    public function getProgressPercentAttribute(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return (int) round(($this->processed / $this->total) * 100);
    }
}
