<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

class RateLimitException extends AbstractParserException
{
    public function __construct(string $url, public readonly int $retryAfterSeconds = 60)
    {
        parent::__construct("Превышен лимит запросов, повтор через {$retryAfterSeconds}с", $url);
    }
}
