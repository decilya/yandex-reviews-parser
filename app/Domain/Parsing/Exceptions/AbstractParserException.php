<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

use RuntimeException;

abstract class AbstractParserException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $url,
        public readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
