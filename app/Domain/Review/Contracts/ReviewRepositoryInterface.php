<?php

declare(strict_types=1);

namespace App\Domain\Review\Contracts;

use App\Domain\Review\DTO\ReviewDto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Контракт репозитория отзывов.
 */
interface ReviewRepositoryInterface
{
    /**
     * Массовое обновление/создание отзывов (идемпотентно).
     *
     * @param int $organizationId ID организации
     * @param ReviewDto[] $reviews Массив DTO отзывов
     * @return int Количество обработанных записей
     */
    public function upsertMany(int $organizationId, array $reviews): int;

    /**
     * Пагинация отзывов организации.
     *
     * @param int $organizationId ID организации
     * @param int $perPage Количество на страницу
     * @return LengthAwarePaginator
     */
    public function paginateByOrganization(int $organizationId, int $perPage = 50): LengthAwarePaginator;

    /**
     * Количество отзывов организации.
     *
     * @param int $organizationId ID организации
     * @return int
     */
    public function countByOrganization(int $organizationId): int;
}
