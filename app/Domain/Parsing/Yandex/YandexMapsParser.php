<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Yandex;

use App\Domain\Organization\DTO\OrganizationDto;
use App\Domain\Parsing\Abstract\AbstractPlatformParser;
use App\Domain\Parsing\Contracts\PageFetcherInterface;
use App\Domain\Parsing\DTO\ParsingResultDto;
use App\Domain\Parsing\Exceptions\BlockedException;
use App\Domain\Parsing\Exceptions\LayoutChangedException;
use App\Domain\Review\DTO\ReviewDto;
use Illuminate\Support\Facades\Log;

/**
 * Парсер Яндекс.Карт.
 */
class YandexMapsParser extends AbstractPlatformParser
{
    /** @var YandexResponseAdapter */
    private YandexResponseAdapter $adapter;

    /**
     * @param PageFetcherInterface $fetcher Загрузчик
     * @param YandexResponseAdapter|null $adapter Адаптер
     */
    public function __construct(
        PageFetcherInterface $fetcher,
        ?YandexResponseAdapter $adapter = null,
    ) {
        parent::__construct($fetcher);
        $this->adapter = $adapter ?? new YandexResponseAdapter();
    }

    /**
     * Поддерживает ли URL.
     * @param string $url URL
     * @return bool
     */
    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }
        return (bool) preg_match('#^(www\.|maps\.)?yandex\.(ru|com|kz|by|uz|com\.tr)$#i', $host);
    }

    /**
     * Парсинг.
     * @param string $url URL
     * @return ParsingResultDto Результат
     */
    public function parse(string $url): ParsingResultDto
    {
        $this->layoutFailureCount = 0;

        Log::info('Parsing started', ['url' => $url]);

        $reviewsUrl = $this->normalizeToReviewsUrl($url);

        $html = $this->fetchPage($reviewsUrl);
        $this->checkForBlock($html, $reviewsUrl);

        $stateData = $this->adapter->extractStateViewFromHtml($html, $reviewsUrl);
        $organizationDto = $this->adapter->extractOrganizationFromStateView($stateData, $reviewsUrl);

        $allReviews = $this->adapter->extractReviewsFromStateView($stateData, $reviewsUrl);

        $source = 'state-view';

        if (empty($allReviews)) {
            Log::info('Falling back to DOM', ['url' => $reviewsUrl]);
            $allReviews = $this->adapter->extractReviewsFromDom($html, $reviewsUrl);
            $source = 'dom';
        }

        Log::info('First page parsed', [
            'url' => $reviewsUrl,
            'source' => $source,
            'reviews_on_page' => count($allReviews),
        ]);

        $maxPages = config('parsing.max_pages', 30);
        $currentPage = 1;

        while ($currentPage < $maxPages) {
            if (!$this->adapter->hasNextPage($html, $currentPage)) {
                break;
            }

            $currentPage++;
            $pageUrl = $this->addPageParam($reviewsUrl, $currentPage);

            $minMs = config('parsing.throttle_min_ms', 500);
            $maxMs = config('parsing.throttle_max_ms', 1500);
            usleep(rand($minMs * 1000, $maxMs * 1000));

            try {
                $pageHtml = $this->fetchPage($pageUrl);
                $this->checkForBlock($pageHtml, $pageUrl);

                $pageStateData = $this->adapter->extractStateViewFromHtml($pageHtml, $pageUrl);
                $pageReviews = $this->adapter->extractReviewsFromStateView($pageStateData, $pageUrl);

                if (empty($pageReviews)) {
                    $pageReviews = $this->adapter->extractReviewsFromDom($pageHtml, $pageUrl);
                }

                if (empty($pageReviews)) {
                    break;
                }

                $allReviews = array_merge($allReviews, $pageReviews);
                $html = $pageHtml;

                Log::debug('Page fetched', [
                    'page' => $currentPage,
                    'fetched' => count($pageReviews),
                    'total' => count($allReviews),
                ]);
            } catch (LayoutChangedException $e) {
                $this->handleLayoutFailure('page_' . $currentPage, $pageUrl);
                break;
            }
        }

        $allReviews = $this->deduplicateReviews($allReviews);

        $this->validateResult($organizationDto, $allReviews, $reviewsUrl);

        Log::info('Parsing completed', [
            'url' => $reviewsUrl,
            'total_reviews' => count($allReviews),
            'pages_parsed' => $currentPage,
        ]);

        return new ParsingResultDto($organizationDto, $allReviews);
    }

    /**
     * Дедупликация отзывов.
     * @param ReviewDto[] $reviews Отзывы
     * @return ReviewDto[] Уникальные
     */
    private function deduplicateReviews(array $reviews): array
    {
        $seen = [];
        $unique = [];

        foreach ($reviews as $review) {
            if (!isset($seen[$review->externalId])) {
                $seen[$review->externalId] = true;
                $unique[] = $review;
            }
        }

        return $unique;
    }

    /**
     * Извлечь метрики.
     * @param string $content HTML
     * @param string $url URL
     * @return OrganizationDto
     */
    protected function extractOrganizationData(string $content, string $url): OrganizationDto
    {
        $stateData = $this->adapter->extractStateViewFromHtml($content, $url);
        return $this->adapter->extractOrganizationFromStateView($stateData, $url);
    }

    /**
     * Извлечь отзывы.
     * @param string $content HTML
     * @param string $url URL
     * @return array
     */
    protected function extractAllReviews(string $content, string $url): array
    {
        $stateData = $this->adapter->extractStateViewFromHtml($content, $url);
        $reviews = $this->adapter->extractReviewsFromStateView($stateData, $url);

        if (empty($reviews)) {
            $reviews = $this->adapter->extractReviewsFromDom($content, $url);
        }

        return $reviews;
    }


    /**
     * Нормализовать URL к /reviews/.
     * @param string $url URL
     * @return string
     */
    private function normalizeToReviewsUrl(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = rtrim($parsed['path'] ?? '', '/');

        $path = preg_replace('#/(gallery|prices|features|reviews|posts|photos)(?:/|$)#', '', $path);
        $path .= '/reviews';

        $query = $parsed['query'] ?? '';
        parse_str($query, $queryParams);
        unset($queryParams['page']);
        $query = http_build_query($queryParams);

        $result = "{$scheme}://{$host}{$path}/";
        if ($query !== '') {
            $result .= '?' . $query;
        }

        return $result;
    }

    /**
     * Добавить параметр страницы.
     * @param string $url URL
     * @param int $page Номер страницы
     * @return string
     */
    private function addPageParam(string $url, int $page): string
    {
        $parsed = parse_url($url);
        $query = $parsed['query'] ?? '';
        parse_str($query, $queryParams);
        $queryParams['page'] = $page;
        $query = http_build_query($queryParams);

        $base = "{$parsed['scheme']}://{$parsed['host']}{$parsed['path']}";
        return $query !== '' ? "{$base}?{$query}" : $base;
    }


    /**
     * Проверка на блокировку.
     * @param string $content HTML
     * @param string $url URL
     */
    protected function checkForBlock(string $content, string $url): void
    {
        // Точные маркеры страницы капчи
        if (str_contains($content, 'showcaptcha')
            || str_contains($content, 'smart-captcha')
            || str_contains($content, 'Подтвердите, что запросы отправляли вы')
        ) {
            throw new BlockedException('Обнаружена капча', $url, ['reason' => 'captcha']);
        }

        // Точные маркеры бана (короткая страница без обычного контента)
        if (strlen($content) < 1000 && (
                str_contains($content, 'Доступ ограничен')
                || str_contains($content, 'Доступ запрещён')
            )) {
            throw new BlockedException('Доступ запрещён', $url, ['reason' => 'access_denied']);
        }
    }
}
