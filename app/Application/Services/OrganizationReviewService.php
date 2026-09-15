<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Jobs\ParseOrganizationReviewsJob;
use App\Domain\Organization\Contracts\OrganizationRepositoryInterface;
use App\Domain\Organization\Models\Organization;
use App\Domain\Parsing\DTO\ParsingResultDto;
use App\Domain\Parsing\Exceptions\AbstractParserException;
use App\Domain\Parsing\Exceptions\BlockedException;
use App\Domain\Parsing\Exceptions\EmptyResponseException;
use App\Domain\Parsing\Exceptions\LayoutChangedException;
use App\Domain\Parsing\Factory\PlatformParserFactory;
use App\Domain\Review\Contracts\ReviewRepositoryInterface;
use App\Domain\Review\Models\ReviewSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис работы с организациями и отзывами.
 */
class OrganizationReviewService
{
    /**
     * @param OrganizationRepositoryInterface $organizationRepo Репозиторий организаций
     * @param ReviewRepositoryInterface $reviewRepo Репозиторий отзывов
     * @param PlatformParserFactory $parserFactory Фабрика парсеров
     */
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepo,
        private readonly ReviewRepositoryInterface $reviewRepo,
        private readonly PlatformParserFactory $parserFactory,
    ) {
    }

    /**
     * Сохранить организацию и запустить парсинг.
     * @param int $userId ID пользователя
     * @param string $yandexUrl Ссылка
     * @return Organization
     */
    public function saveOrganizationAndParse(int $userId, string $yandexUrl): Organization
    {
        $organization = $this->organizationRepo->findByUser($userId);

        if ($organization) {
            $this->organizationRepo->update($organization, [
                'yandex_url' => $yandexUrl,
                'status' => 'pending',
                'last_error' => null,
            ]);
        } else {
            $organization = $this->organizationRepo->create([
                'user_id' => $userId,
                'yandex_url' => $yandexUrl,
                'status' => 'pending',
            ]);
        }

        ParseOrganizationReviewsJob::dispatch($organization->id);

        return $organization;
    }

    /**
     * Выполнить парсинг.
     * @param Organization $organization Организация
     */
    public function executeParsing(Organization $organization): void
    {
        try {
            $parser = $this->parserFactory->create($organization->yandex_url);
            $result = $parser->parse($organization->yandex_url);

            $this->persistResult($organization, $result);

            $this->organizationRepo->update($organization, [
                'status' => 'parsed',
                'last_error' => null,
                'last_parsed_at' => now(),
            ]);
        } catch (AbstractParserException $e) {
            Log::error('Parsing failed', [
                'organization_id' => $organization->id,
                'url' => $e->url,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $status = $this->mapExceptionToStatus($e);

            $this->organizationRepo->update($organization, [
                'status' => $status,
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Сохранить результат парсинга.
     * @param Organization $organization Организация
     * @param ParsingResultDto $result Результат
     */
    private function persistResult(Organization $organization, ParsingResultDto $result): void
    {
        DB::transaction(function () use ($organization, $result) {
            $this->organizationRepo->update($organization, [
                'name' => $result->organization->name,
                'yandex_place_id' => $result->organization->placeId,
                'rating' => $result->organization->rating,
                'rating_count' => $result->organization->ratingCount,
                'review_count' => $result->organization->reviewCount,
            ]);

            $this->reviewRepo->upsertMany($organization->id, $result->reviews);

            ReviewSnapshot::create([
                'organization_id' => $organization->id,
                'rating' => $result->organization->rating,
                'rating_count' => $result->organization->ratingCount,
                'review_count' => $result->organization->reviewCount,
                'reviews_fetched' => count($result->reviews),
                'snapshotted_at' => now(),
            ]);
        });
    }

    /**
     * Маппинг исключения на статус.
     * @param AbstractParserException $e Исключение
     * @return string Статус
     */
    private function mapExceptionToStatus(AbstractParserException $e): string
    {
        return match (true) {
            $e instanceof LayoutChangedException => 'requires_attention',
            $e instanceof BlockedException => 'error',
            $e instanceof EmptyResponseException => 'requires_attention',
            default => 'error',
        };
    }
}
