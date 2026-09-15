<?php

declare(strict_types=1);

namespace App\Domain\Review\Repositories;

use App\Domain\Review\Contracts\ReviewRepositoryInterface;
use App\Domain\Review\DTO\ReviewDto;
use App\Domain\Review\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Репозиторий отзывов.
 */
class EloquentReviewRepository implements ReviewRepositoryInterface
{
    /**
     * Массовый upsert.
     * @param int $organizationId ID организации
     * @param ReviewDto[] $reviews Отзывы
     * @return int
     */
    public function upsertMany(int $organizationId, array $reviews): int
    {
        if (empty($reviews)) {
            return 0;
        }

        $upsertData = array_map(fn(ReviewDto $dto) => [
            'organization_id' => $organizationId,
            'external_id' => $dto->externalId,
            'author_name' => $dto->authorName,
            'author_avatar_url' => $dto->authorAvatarUrl,
            'rating' => $dto->rating,
            'text' => $dto->text,
            'published_at' => $dto->publishedAt?->format('Y-m-d H:i:s'),
        ], $reviews);

        return Review::upsert(
            $upsertData,
            ['organization_id', 'external_id'],
            ['author_name', 'author_avatar_url', 'rating', 'text', 'published_at'],
        );
    }

    /**
     * Пагинация.
     * @param int $organizationId ID организации
     * @param int $perPage На страницу
     * @return LengthAwarePaginator
     */
    public function paginateByOrganization(int $organizationId, int $perPage = 50): LengthAwarePaginator
    {
        return Review::where('organization_id', $organizationId)
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    /**
     * Кол-во отзывов.
     * @param int $organizationId ID организации
     * @return int
     */
    public function countByOrganization(int $organizationId): int
    {
        return Review::where('organization_id', $organizationId)->count();
    }
}
