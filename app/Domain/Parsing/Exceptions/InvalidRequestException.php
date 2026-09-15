<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

class InvalidRequestException extends AbstractParserException
{
    public function __construct(string $url, int $statusCode, array $context = [])
    {
        parent::__construct(
            "Некорректный запрос к источнику (HTTP {$statusCode})",
            $url,
            array_merge($context, ['status_code' => $statusCode]),
        );
    }
}
