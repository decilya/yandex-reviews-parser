<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Parsing\Contracts\PageFetcherInterface;
use App\Domain\Parsing\Yandex\YandexMapsParser;
use App\Domain\Parsing\Yandex\YandexResponseAdapter;
use Tests\TestCase;


/**
 * Тесты парсера Яндекс.Карт.
 */
class YandexMapsParserTest extends TestCase{
    /**
     * Тест поддержки URL.
     */
    public function test_supports_yandex_urls(): void
    {
        $parser = new YandexMapsParser($this->createMock(PageFetcherInterface::class));
        $this->assertTrue($parser->supports('https://yandex.ru/maps/org/123/'));
        $this->assertTrue($parser->supports('https://www.yandex.ru/maps/org/123/'));
        $this->assertFalse($parser->supports('https://google.com/maps'));
    }

    /**
     * Тест парсинга с state-view.
     */
    public function test_parse_uses_state_view_when_reviews_present(): void
    {
        $html = $this->getLargeOrgHtml();
        $fetcher = $this->createMock(PageFetcherInterface::class);
        $fetcher->method('fetch')->willReturn($html);

        $parser = new YandexMapsParser($fetcher, new YandexResponseAdapter());
        $result = $parser->parse('https://yandex.ru/maps/org/park_ptits_vorobyi/1028810460/');

        $this->assertEquals('Парк птиц Воробьи', $result->organization->name);
        $this->assertNotEmpty($result->reviews);
    }

    /**
     * Тест fallback на DOM.
     */
    public function test_parse_falls_back_to_dom(): void
    {
        $html = $this->getSmallOrgHtml();
        $fetcher = $this->createMock(PageFetcherInterface::class);
        $fetcher->method('fetch')->willReturn($html);

        $parser = new YandexMapsParser($fetcher, new YandexResponseAdapter());
        $result = $parser->parse('https://yandex.ru/maps/org/beze/199594228057/');

        $this->assertEquals('Безе', $result->organization->name);
        $this->assertNotEmpty($result->reviews);
        $this->assertStringStartsWith('dom_', $result->reviews[0]->externalId);
    }

    /**
     * Фикстура большой организации.
     * @return string
     */
    private function getLargeOrgHtml(): string
    {
        return <<<'HTML'
<html><body>
<h1 itemprop="name">Парк птиц Воробьи</h1>
<script type="application/json" class="state-view">{"config":{"csrfToken":"abc:123"},"landing":{"orgId":1028810460},"meta":{"h1":"Парк птиц Воробьи"},"ratingData":{"ratingCount":43829,"ratingValue":5,"reviewCount":16897},"reviewResults":{"reviews":[{"reviewId":"r1","author":{"name":"Ольга Антипина","avatarUrl":"https://example.com"},"text":"Интересное место","rating":5,"updatedTime":"2026-08-25T16:45:34.971Z"}]}}</script>
</body></html>
HTML;
    }

    /**
     * Фикстура малой организации.
     * @return string
     */
    private function getSmallOrgHtml(): string
    {
        return <<<'HTML'
<html><body>
<h1 itemprop="name">Безе</h1>
<div itemprop="review" itemscope="">
    <div itemprop="author" itemscope="">
        <meta itemprop="image" content="https://example.com/ava"/>
        <span itemprop="name">Алёна И.</span>
    </div>
    <meta itemprop="ratingValue" content="5.0"/>
    <meta itemprop="datePublished" content="2026-08-20T12:00:00.000Z"/>
    <div itemprop="reviewBody">Отличная кофейня!</div>
</div>
<script type="application/json" class="state-view">{"config":{"csrfToken":"abc:123"},"landing":{"orgId":199594228057},"meta":{"title":"Безе"},"ratingData":{"ratingCount":36,"ratingValue":4.7,"reviewCount":29}}</script>
</body></html>
HTML;
    }
}
