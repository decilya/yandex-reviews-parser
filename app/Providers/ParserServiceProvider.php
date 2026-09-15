<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Organization\Contracts\OrganizationRepositoryInterface;
use App\Domain\Organization\Repositories\EloquentOrganizationRepository;
use App\Domain\Parsing\Contracts\PageFetcherInterface;
use App\Domain\Parsing\Factory\PlatformParserFactory;
use App\Domain\Parsing\Yandex\YandexMapsParser;
use App\Domain\Parsing\Yandex\YandexPageFetcher;
use App\Domain\Parsing\Yandex\YandexResponseAdapter;
use App\Domain\Review\Contracts\ReviewRepositoryInterface;
use App\Domain\Review\Repositories\EloquentReviewRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Провайдер парсинга.
 */
class ParserServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов.
     */
    public function register(): void
    {
        $this->app->bind(OrganizationRepositoryInterface::class, EloquentOrganizationRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, EloquentReviewRepository::class);
        $this->app->bind(PageFetcherInterface::class, YandexPageFetcher::class);
        $this->app->bind(YandexResponseAdapter::class, YandexResponseAdapter::class);
        $this->app->bind(YandexMapsParser::class, function ($app) {
            return new YandexMapsParser(
                $app->make(PageFetcherInterface::class),
                $app->make(YandexResponseAdapter::class),
            );
        });
        $this->app->bind(PlatformParserFactory::class, function ($app) {
            return new PlatformParserFactory([
                $app->make(YandexMapsParser::class),
            ]);
        });
    }

    /**
     * Загрузка сервисов.
     */
    public function boot(): void
    {
    }
}
