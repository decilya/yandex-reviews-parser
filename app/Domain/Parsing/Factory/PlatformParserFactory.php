<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Factory;

use App\Domain\Parsing\Contracts\ReviewParserInterface;
use App\Domain\Parsing\Exceptions\InvalidUrlException;

/**
 * Фабрика парсеров.
 */
class PlatformParserFactory
{
    /**
     * @param ReviewParserInterface[] $parsers Массив парсеров
     */
    public function __construct(private readonly array $parsers)
    {
    }

    /**
     * Создать парсер для URL.
     * @param string $url URL
     * @return ReviewParserInterface Парсер
     */
    public function create(string $url): ReviewParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($url)) {
                return $parser;
            }
        }

        throw new InvalidUrlException($url);
    }
}
