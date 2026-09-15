<?php
// app/Domain/Review/DTO/ReviewDto.php
// DTO для передачи данных отзыва между слоями парсинга и сервиса.

declare(strict_types=1);

namespace App\Domain\Review\DTO;

use DateTimeImmutable;

/**
 * DTO отзыва.
 */
readonly class ReviewDto
{
    /**
     * Конструктор DTO отзыва.
     *
     * @param string $externalId Внешний идентификатор отзыва
     * @param string $authorName Имя автора
     * @param string|null $authorAvatarUrl URL аватара автора
     * @param int $rating Оценка (1-5)
     * @param string|null $text Текст отзыва
     * @param DateTimeImmutable|null $publishedAt Дата публикации
     */
    public function __construct(
        public string $externalId,
        public string $authorName,
        public ?string $authorAvatarUrl,
        public int $rating,
        public ?string $text,
        public ?DateTimeImmutable $publishedAt,
    ) {
    }
}
