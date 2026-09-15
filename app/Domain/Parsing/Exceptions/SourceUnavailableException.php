<?php
// app/Domain/Parsing/Exceptions/SourceUnavailableException.php
// Источник временно недоступен (3xx, 5xx ошибки).

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

/**
 * Источник временно недоступен (3xx, 5xx ошибки).
 */
class SourceUnavailableException extends AbstractParserException
{
    public function __construct(string $url, int $statusCode, array $context = [])
    {
        parent::__construct(
            "Источник временно недоступен (HTTP {$statusCode})",
            $url,
            array_merge($context, ['status_code' => $statusCode]),
        );
    }
}
