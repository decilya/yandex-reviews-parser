<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

/**
 * Исключение: заблокировано источником (капча, бан).
 */
class BlockedException extends AbstractParserException
{
    /**
     * @param string $message Сообщение
     * @param string $url URL
     * @param array<string, mixed> $context Контекст
     */
    public function __construct(string $message, string $url, array $context = [])
    {
        parent::__construct($message, $url, $context);
    }
}
