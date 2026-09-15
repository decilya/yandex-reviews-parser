<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

class EmptyResponseException extends AbstractParserException
{
    public function __construct(string $url, array $context = [])
    {
        parent::__construct('Получен пустой ответ от источника', $url, $context);
    }
}
