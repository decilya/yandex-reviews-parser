<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Parsing\Exceptions\LayoutChangedException;
use App\Domain\Parsing\Yandex\YandexResponseAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Тесты адаптера ответов Яндекса.
 */
class YandexResponseAdapterTest extends TestCase
{
    /** @var YandexResponseAdapter */
    private YandexResponseAdapter $adapter;

    /**
     * setUp.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new YandexResponseAdapter();
    }

    /**
     * Тест извлечения метрик.
     */
    public function test_extracts_organization_from_park_vorobyi(): void
    {
        $stateData = $this->getParkVorobyiStateView();
        $result = $this->adapter->extractOrganizationFromStateView($stateData, 'https://yandex.ru/maps/org/park_ptits_vorobyi/1028810460/reviews/');
        $this->assertEquals('Парк птиц Воробьи', $result->name);
        $this->assertEquals('1028810460', $result->placeId);
        $this->assertEquals(5.0, $result->rating);
        $this->assertEquals(43829, $result->ratingCount);
        $this->assertEquals(16897, $result->reviewCount);
    }

    /**
     * Тест извлечения метрик Безе.
     */
    public function test_extracts_organization_from_beze(): void
    {
        $stateData = $this->getBezeStateView();
        $result = $this->adapter->extractOrganizationFromStateView($stateData, 'https://yandex.ru/maps/org/beze/199594228057/');
        $this->assertEquals('Безе', $result->name);
        $this->assertEquals('199594228057', $result->placeId);
    }

    /**
     * Тест очистки имени с кавычками.
     */
    public function test_cleans_name_with_quotes(): void
    {
        $stateData = [
            'meta' => ['h1' => 'Отзывы о «Парк птиц Воробьи»'],
            'ratingData' => ['ratingValue' => 5, 'ratingCount' => 100, 'reviewCount' => 50],
            'landing' => ['orgId' => 123],
        ];
        $result = $this->adapter->extractOrganizationFromStateView($stateData, 'https://yandex.ru/maps/org/123/');
        $this->assertEquals('Парк птиц Воробьи', $result->name);
    }

    /**
     * Тест очистки имени с запятой.
     */
    public function test_cleans_name_with_comma(): void
    {
        $stateData = [
            'meta' => ['title' => 'Безе, кофейня, Краснодарский край — Яндекс Карты'],
            'ratingData' => ['ratingValue' => 4.7, 'ratingCount' => 36, 'reviewCount' => 29],
            'landing' => ['orgId' => 199594228057],
        ];
        $result = $this->adapter->extractOrganizationFromStateView($stateData, 'https://yandex.ru/maps/org/199594228057/');
        $this->assertEquals('Безе', $result->name);
    }

    /**
     * Тест пустых отзывов.
     */
    public function test_returns_empty_reviews_when_no_review_results(): void
    {
        $stateData = $this->getBezeStateView();
        $reviews = $this->adapter->extractReviewsFromStateView($stateData, 'https://yandex.ru/maps/org/beze/199594228057/');
        $this->assertEmpty($reviews);
    }

    /**
     * Тест извлечения отзывов из DOM.
     */
    public function test_extracts_reviews_from_dom(): void
    {
        $html = $this->getBezeDomHtml();
        $reviews = $this->adapter->extractReviewsFromDom($html, 'https://yandex.ru/maps/org/beze/199594228057/');
        $this->assertNotEmpty($reviews);
        $this->assertGreaterThan(0, $reviews[0]->rating);
    }

    /**
     * Тест пагинации.
     */
    public function test_detects_next_page_in_pagination(): void
    {
        $html = '<ol class="seo-pagination-view"><li><a href="/reviews/?page=2">2</a></li></ol>';
        $this->assertTrue($this->adapter->hasNextPage($html, 1));
        $this->assertFalse($this->adapter->hasNextPage($html, 2));
    }

    /**
     * Тест отсутствия state-view.
     */
    public function test_throws_when_state_view_not_found(): void
    {
        $this->expectException(LayoutChangedException::class);
        $this->adapter->extractStateViewFromHtml('<html></html>', 'https://yandex.ru/maps/org/123/');
    }

    /**
     * Фикстура Парк птиц Воробьи.
     * @return array
     */
    private function getParkVorobyiStateView(): array
    {
        return [
            'config' => ['csrfToken' => 'abc:123'],
            'landing' => ['orgId' => 1028810460],
            'meta' => ['title' => 'Отзывы о «Парк птиц Воробьи»', 'h1' => 'Отзывы о Парк птиц Воробьи'],
            'ratingData' => ['ratingCount' => 43829, 'ratingValue' => 5, 'reviewCount' => 16897],
            'reviewResults' => [
                'reviews' => [
                    [
                        'reviewId' => 'r1',
                        'businessId' => '1028810460',
                        'author' => ['name' => 'Ольга Антипина', 'avatarUrl' => 'https://example.com'],
                        'text' => 'Интересное место.',
                        'rating' => 5,
                        'updatedTime' => '2026-08-25T16:45:34.971Z',
                    ],
                ],
            ],
        ];
    }

    /**
     * Фикстура Безе.
     * @return array
     */
    private function getBezeStateView(): array
    {
        return [
            'config' => ['csrfToken' => 'abc:123'],
            'landing' => ['orgId' => 199594228057],
            'meta' => ['title' => 'Безе, кофейня — Яндекс Карты'],
            'ratingData' => ['ratingCount' => 36, 'ratingValue' => 4.7, 'reviewCount' => 29],
        ];
    }

    /**
     * Фикстура DOM Безе.
     * @return string
     */
    private function getBezeDomHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<div itemprop="review" itemtype="http://schema.org/Review" itemscope="">
    <div itemprop="author" itemtype="http://schema.org/Person" itemscope="">
        <meta itemprop="image" content="https://avatars.mds.yandex.net/get-yapic/123/islands-68"/>
        <span itemprop="name">Алёна И.</span>
    </div>
    <meta itemprop="ratingValue" content="5.0"/>
    <meta itemprop="datePublished" content="2026-08-20T12:00:00.000Z"/>
    <div itemprop="reviewBody">Отличная кофейня!</div>
</div>
</body>
</html>
HTML;
    }
}
