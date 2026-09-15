<?php
// app/Domain/Parsing/DTO/ParsingResultDto.php
// DTO — результат полного парсинга: метрики + массив отзывов.

declare(strict_types=1);

namespace App\Domain\Parsing\DTO;

use App\Domain\Organization\DTO\OrganizationDto;
use App\Domain\Review\DTO\ReviewDto;

/**
 * Результат парсинга.
 */
readonly class ParsingResultDto
{
    public function __construct(
        public OrganizationDto $organization,
        /** @var ReviewDto[] */
        public array $reviews,
    ) {
    }
}
