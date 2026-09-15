<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Contracts;

use App\Domain\Parsing\DTO\ParsingResultDto;

/**
 * Интерфейс парсера отзывов.
 */
interface ReviewParserInterface
{
    /**
     * Парсит все доступные отзывы и метрики организации.
     *
     * @param string $url Ссылка на карточку организации
     * @return ParsingResultDto Результат парсинга
     */
    public function parse(string $url): ParsingResultDto;

    /**
     * Может ли данный парсер обработать эту ссылку?
     */
    public function supports(string $url): bool;
}
