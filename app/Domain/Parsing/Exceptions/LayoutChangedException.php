<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

/**
 * Разметка Яндекса изменилась — парсер не может найти ожидаемые данные.
 */
class LayoutChangedException extends AbstractParserException
{
    public function __construct(string $url, string $missingField, array $context = [])
    {
        parent::__construct(
            "Разметка изменилась: ожидаемое поле '{$missingField}' не найдено",
            $url,
            array_merge($context, ['missing_field' => $missingField]),
        );
    }
}
