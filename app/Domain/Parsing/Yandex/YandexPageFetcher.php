<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Yandex;

use App\Domain\Parsing\Contracts\PageFetcherInterface;
use App\Domain\Parsing\Exceptions\BlockedException;
use App\Domain\Parsing\Exceptions\InvalidRequestException;
use App\Domain\Parsing\Exceptions\RateLimitException;
use App\Domain\Parsing\Exceptions\SourceUnavailableException;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP-загрузчик страниц Яндекса.
 */
class YandexPageFetcher implements PageFetcherInterface
{
    /**
     * Получить HTML.
     * @param string $url URL
     * @param array<string, mixed> $options Опции
     * @return string HTML
     */
    public function fetch(string $url, array $options = []): string
    {
        $userAgent = $this->rotateUserAgent();
        $timeout = config('parsing.http_timeout', 30);

        Log::debug('Fetching Yandex page', ['url' => $url]);

        /** @var CookieJar $cookies */
        $cookies = $options['cookies'] ?? new CookieJar();

        $response = Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ])
            ->withOptions(['cookies' => $cookies])
            ->get($url);

        $this->handleResponseErrors($response, $url);

        return $response->body();
    }

    /**
     * Получить JSON.
     * @param string $url URL
     * @param array<string, mixed> $params Параметры
     * @param array<string, mixed> $options Опции
     * @return array<string, mixed> JSON
     */
    public function fetchJson(string $url, array $params = [], array $options = []): array
    {
        $userAgent = $this->rotateUserAgent();
        $timeout = config('parsing.http_timeout', 30);

        $headers = [
            'User-Agent' => $userAgent,
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        $csrfToken = $options['csrf_token'] ?? null;
        if (is_string($csrfToken) && $csrfToken !== '') {
            $headers['x-csrf-token'] = $csrfToken;
        }

        $headers['Referer'] = $options['referer'] ?? 'https://yandex.ru/maps/';

        /** @var CookieJar $cookies */
        $cookies = $options['cookies'] ?? new CookieJar();

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->withOptions(['cookies' => $cookies])
            ->get($url, $params);

        $this->handleResponseErrors($response, $url);

        return $response->json() ?? [];
    }

    /**
     * Обработка ошибок HTTP.
     * @param Response $response Ответ
     * @param string $url URL
     */
    private function handleResponseErrors(Response $response, string $url): void
    {
        $status = $response->status();

        if ($status === 429) {
            $retryAfter = (int) $response->header('Retry-After', 60);
            throw new RateLimitException($url, $retryAfter);
        }

        if ($status === 403) {
            throw new BlockedException('Заблокировано (HTTP 403)', $url);
        }

        if ($status >= 300 && $status < 400) {
            throw new SourceUnavailableException($url, $status);
        }

        if ($status >= 400 && $status < 500) {
            throw new InvalidRequestException($url, $status);
        }

        if ($status >= 500) {
            throw new SourceUnavailableException($url, $status);
        }
    }

    /**
     * Ротация User-Agent.
     * @return string
     */
    private function rotateUserAgent(): string
    {
        $userAgents = config('parsing.user_agents', []);
        if (empty($userAgents)) {
            return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        }
        return $userAgents[array_rand($userAgents)];
    }
}
