<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Contracts;

/**
 * Интерфейс загрузчика страниц.
 */
interface PageFetcherInterface
{
    /**
     * Получает HTML-содержимое страницы по URL.
     */
    public function fetch(string $url, array $options = []): string;

    /**
     * Получает JSON-данные по URL (для внутренних API).
     */
    public function fetchJson(string $url, array $params = [], array $options = []): array;
}
