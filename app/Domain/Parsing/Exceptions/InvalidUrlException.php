<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Exceptions;

use InvalidArgumentException;

class InvalidUrlException extends InvalidArgumentException
{
    public function __construct(string $url)
    {
        parent::__construct("Некорректная ссылка на Яндекс.Карты: {$url}");
    }
}
