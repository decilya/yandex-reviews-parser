<?php

declare(strict_types=1);

namespace App\Domain\Organization\DTO;

/**
 * DTO (Data Transfer Object) организации.
 *
 * Используется для передачи метрик организации из парсера в сервис.
 * Immutable (readonly) — после создания данные не меняются.
 */
readonly class OrganizationDto
{
    /**
     * Конструктор DTO организации.
     *
     * @param string $name Название организации
     * @param string $placeId Идентификатор места на Яндекс.Картах (orgId)
     * @param float $rating Средний рейтинг (от 1.0 до 5.0)
     * @param int $ratingCount Количество оценок (включая оценки без текста)
     * @param int $reviewCount Количество отзывов (с текстом)
     */
    public function __construct(
        public string $name,
        public string $placeId,
        public float $rating,
        public int $ratingCount,
        public int $reviewCount,
    ) {
    }
}
