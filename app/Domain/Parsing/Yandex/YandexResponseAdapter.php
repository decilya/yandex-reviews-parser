<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Yandex;

use App\Domain\Organization\DTO\OrganizationDto;
use App\Domain\Parsing\Exceptions\LayoutChangedException;
use App\Domain\Review\DTO\ReviewDto;
use DateTimeImmutable;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Адаптер ответов Яндекса.
 *
 * Реальная структура (верифицирована на карточке Парк птиц Воробьи):
 * - Название: config.meta.h1
 * - orgId: config.landing.orgId
 * - CSRF: config.csrfToken
 * - Метрики: stack.0.results.items.0.ratingData.{ratingValue,reviewCount,ratingCount}
 * - Отзывы: stack.0.results.items.0.reviewResults.reviews[]
 */
class YandexResponseAdapter
{
    /**
     * Извлечь JSON из <script class="state-view">.
     *
     * @throws LayoutChangedException Если state-view не найден
     */
    public function extractStateViewFromHtml(string $html, string $url): array
    {
        $patterns = [
            '/<script[^>]*class="state-view"[^>]*type="application\/json"[^>]*>(.*?)<\/script>/s',
            '/<script[^>]*type="application\/json"[^>]*class="state-view"[^>]*>(.*?)<\/script>/s',
            '/<script[^>]*class="state-view"[^>]*>(.*?)<\/script>/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $json = json_decode($matches[1], true);
                if ($json !== null) {
                    return $json;
                }
            }
        }

        throw new LayoutChangedException($url, 'state-view script', [
            'html_length' => strlen($html),
        ]);
    }

    /**
     * Извлечь CSRF-токен. Реальный путь: config.csrfToken.
     */
    public function extractCsrfToken(array $stateData): ?string
    {
        $csrfToken = $stateData['config']['csrfToken'] ?? null;
        return is_string($csrfToken) ? $csrfToken : null;
    }

    /**
     * Извлечь метрики организации из state-view.
     *
     * @throws LayoutChangedException Если данные не найдены
     */
    public function extractOrganizationFromStateView(array $stateData, string $url): OrganizationDto
    {
        // Название: config.meta.h1 (например "Отзывы о Парк птиц Воробьи")
        $name = $stateData['config']['meta']['h1']
            ?? $stateData['config']['meta']['title']
            ?? null;

        if (is_string($name)) {
            $name = $this->cleanOrganizationName($name);
        }

        if (!is_string($name) || $name === '') {
            throw new LayoutChangedException($url, 'organization name', [
                'config_meta_keys' => array_keys($stateData['config']['meta'] ?? []),
            ]);
        }

        // Метрики: stack.0.results.items.0.ratingData
        $root = $stateData['stack'][0]['results']['items'][0] ?? null;

        if (!is_array($root)) {
            throw new LayoutChangedException($url, 'stack.0.results.items.0', [
                'stack_count' => count($stateData['stack'] ?? []),
            ]);
        }

        $ratingData = $root['ratingData'] ?? null;

        if (!is_array($ratingData)) {
            throw new LayoutChangedException($url, 'ratingData', [
                'root_keys' => array_keys($root),
            ]);
        }

        $rating = (float) ($ratingData['ratingValue'] ?? 0);
        $ratingCount = (int) ($ratingData['ratingCount'] ?? 0);
        $reviewCount = (int) ($ratingData['reviewCount'] ?? 0);

        // orgId: config.landing.orgId
        $placeId = (string) ($stateData['config']['landing']['orgId'] ?? '');

        if ($placeId === '') {
            throw new LayoutChangedException($url, 'orgId', []);
        }

        return new OrganizationDto(
            name: $name,
            placeId: $placeId,
            rating: $rating,
            ratingCount: $ratingCount,
            reviewCount: $reviewCount,
        );
    }

    /**
     * Очистить имя организации от мусора.
     */
    private function cleanOrganizationName(string $name): string
    {
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/Отзывы о\s*[«"](.+?)[»"]/u', $name, $matches)) {
            $name = $matches[1];
        } elseif (preg_match('/^Отзывы о\s+(.+?)(?:\s*[,—–-]\s*Яндекс|$)/u', $name, $matches)) {
            $name = trim($matches[1]);
        } elseif (preg_match('/^(.+?)\s*[—–-]\s*Яндекс/u', $name, $matches)) {
            $name = trim($matches[1]);
        }

        if (str_contains($name, ',')) {
            $name = trim(explode(',', $name)[0]);
        }

        return (string) preg_replace('/\s+/u', ' ', trim($name));
    }

    /**
     * Извлечь отзывы из state-view.
     *
     * Реальный путь: stack.0.results.items.0.reviewResults.reviews[]
     *
     * @return ReviewDto[]
     */
    public function extractReviewsFromStateView(array $stateData, string $url): array
    {
        $reviewsData = $stateData['stack'][0]['results']['items'][0]['reviewResults']['reviews'] ?? null;

        if (!is_array($reviewsData) || empty($reviewsData)) {
            return [];
        }

        $reviews = [];
        $skippedCount = 0;

        foreach ($reviewsData as $reviewData) {
            if (!is_array($reviewData)) {
                $skippedCount++;
                continue;
            }

            $review = $this->adaptSingleReviewFromJson($reviewData);
            if ($review !== null) {
                $reviews[] = $review;
            } else {
                $skippedCount++;
            }
        }

        $threshold = (float) config('parsing.skipped_reviews_threshold', 0.5);
        $totalReviews = count($reviewsData);

        if ($totalReviews > 0 && ($skippedCount / $totalReviews) > $threshold) {
            throw new LayoutChangedException($url, 'review structure in state-view', [
                'total' => $totalReviews,
                'skipped' => $skippedCount,
                'parsed' => count($reviews),
            ]);
        }

        return $reviews;
    }

    /**
     * Адаптировать один отзыв.
     *
     * Реальные поля: reviewId, businessId, author.name, author.avatarUrl,
     * text, rating, updatedTime.
     */
    private function adaptSingleReviewFromJson(array $data): ?ReviewDto
    {
        $externalId = (string) ($data['reviewId'] ?? '');
        $rating = is_numeric($data['rating'] ?? null) ? (int) $data['rating'] : 0;

        if ($externalId === '' || $rating === 0) {
            return null;
        }

        $author = is_array($data['author'] ?? null) ? $data['author'] : [];
        $authorName = is_string($author['name'] ?? null) ? $author['name'] : 'Аноним';
        $authorAvatar = is_string($author['avatarUrl'] ?? null) && $author['avatarUrl'] !== ''
            ? $author['avatarUrl'] : null;

        $text = is_string($data['text'] ?? null) ? $data['text'] : null;

        $publishedAt = null;
        $updatedTime = $data['updatedTime'] ?? null;
        if (is_string($updatedTime) && $updatedTime !== '') {
            try {
                $publishedAt = new DateTimeImmutable($updatedTime);
            } catch (\Throwable) {
                // Игнорируем невалидные даты
            }
        }

        return new ReviewDto(
            externalId: $externalId,
            authorName: $authorName,
            authorAvatarUrl: $authorAvatar,
            rating: $rating,
            text: $text,
            publishedAt: $publishedAt,
        );
    }

    /**
     * Fallback: извлечь отзывы из DOM через Schema.org.
     *
     * Яндекс использует camelCase: itemProp, не itemprop.
     * Селекторы: [itemProp="reviewBody"], [itemProp="ratingValue"], [itemProp="datePublished"].
     *
     * @return ReviewDto[]
     */
    public function extractReviewsFromDom(string $html, string $url): array
    {
        $crawler = new Crawler($html);

        // Контейнеры отзывов: div с классом business-review-view
        $reviewNodes = $crawler->filter('div.business-review-view');

        if ($reviewNodes->count() === 0) {
            // Пробуем найти по itemprop в camelCase
            $reviewNodes = $crawler->filter('[itemProp="review"]');
        }

        if ($reviewNodes->count() === 0) {
            return [];
        }

        $reviews = [];

        $reviewNodes->each(function (Crawler $node) use (&$reviews): void {
            $review = $this->parseReviewFromDomNode($node);
            if ($review !== null) {
                $reviews[] = $review;
            }
        });

        return $reviews;
    }

    /**
     * Распарсить один отзыв из DOM.
     */
    private function parseReviewFromDomNode(Crawler $node): ?ReviewDto
    {
        // Текст: [itemProp="reviewBody"]
        $text = '';
        $bodyNode = $node->filter('[itemProp="reviewBody"]');
        if ($bodyNode->count() > 0) {
            $text = trim($bodyNode->text(''));
        }

        if ($text === '') {
            return null;
        }

        // Автор: [itemProp="author"] [itemProp="name"]
        $authorNode = $node->filter('[itemProp="author"] [itemProp="name"]');
        $authorName = $authorNode->count() > 0 ? trim($authorNode->text('')) : 'Аноним';

        // Аватар: [itemProp="author"] meta[itemProp="image"]
        $avatarNode = $node->filter('[itemProp="author"] meta[itemProp="image"]');
        $authorAvatar = $avatarNode->count() > 0 ? $avatarNode->attr('content') : null;

        // Рейтинг: meta[itemProp="ratingValue"] content
        $ratingNode = $node->filter('[itemProp="ratingValue"]');
        $rating = 0;
        if ($ratingNode->count() > 0) {
            $ratingContent = $ratingNode->attr('content');
            if ($ratingContent !== null) {
                $rating = (int) round((float) $ratingContent);
            }
        }

        if ($rating === 0) {
            return null;
        }

        // Дата: meta[itemProp="datePublished"] content
        $publishedAt = null;
        $dateStr = '';
        $dateNode = $node->filter('[itemProp="datePublished"]');
        if ($dateNode->count() > 0) {
            $dateStr = $dateNode->attr('content') ?? '';
            if ($dateStr !== '') {
                try {
                    $publishedAt = new DateTimeImmutable($dateStr);
                } catch (\Throwable) {
                }
            }
        }

        $textHash = substr(md5($text), 0, 16);
        $datePart = $dateStr !== '' ? substr(md5($dateStr), 0, 8) : 'nodate';
        $authorPart = substr(md5($authorName), 0, 8);
        $externalId = "dom_{$authorPart}_{$datePart}_{$textHash}";

        return new ReviewDto(
            externalId: $externalId,
            authorName: $authorName,
            authorAvatarUrl: $authorAvatar,
            rating: $rating,
            text: $text,
            publishedAt: $publishedAt,
        );
    }

    /**
     * Есть ли следующая страница.
     */
    public function hasNextPage(string $html, int $currentPage): bool
    {
        $nextPage = $currentPage + 1;
        $crawler = new Crawler($html);
        $found = false;

        $paginationLinks = $crawler->filter('.seo-pagination-view a[href*="page="]');
        $paginationLinks->each(function (Crawler $node) use ($nextPage, &$found): void {
            $href = $node->attr('href') ?? '';
            if (preg_match('/[?&]page=' . $nextPage . '(?:&|$|#)/', $href)) {
                $found = true;
            }
        });

        if ($found) {
            return true;
        }

        $allLinks = $crawler->filter('a[href*="page=' . $nextPage . '"]');
        $allLinks->each(function (Crawler $node) use ($nextPage, &$found): void {
            $href = $node->attr('href') ?? '';
            if (preg_match('/[?&]page=' . $nextPage . '(?:&|$|#)/', $href)) {
                $found = true;
            }
        });

        return $found;
    }
}
