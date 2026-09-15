<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use App\Application\Services\OrganizationReviewService;
use App\Domain\Organization\Contracts\OrganizationRepositoryInterface;
use App\Domain\Parsing\Exceptions\BlockedException;
use App\Domain\Parsing\Exceptions\InvalidRequestException;
use App\Domain\Parsing\Exceptions\LayoutChangedException;
use App\Domain\Parsing\Exceptions\RateLimitException;
use App\Domain\Parsing\Models\ParsingJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание парсинга отзывов.
 */
class ParseOrganizationReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Кол-во попыток */
    public int $tries;

    /**
     * @param int $organizationId ID организации
     */
    public function __construct(public readonly int $organizationId)
    {
        $this->tries = (int) config('parsing.max_retries', 3);
    }

    /**
     * Задержки между попытками.
     * @return array
     */
    public function backoff(): array
    {
        return config('parsing.backoff', [60, 300, 900]);
    }

    /**
     * Выполнить задание.
     * @param OrganizationReviewService $service Сервис
     * @param OrganizationRepositoryInterface $orgRepo Репозиторий
     */
    public function handle(
        OrganizationReviewService $service,
        OrganizationRepositoryInterface $orgRepo,
    ): void {
        $organization = $orgRepo->findById($this->organizationId);

        if (!$organization) {
            Log::warning('Organization not found', ['id' => $this->organizationId]);
            return;
        }

        $parsingJob = ParsingJob::create([
            'organization_id' => $organization->id,
            'status' => 'processing',
        ]);

        $orgRepo->update($organization, ['status' => 'parsing']);

        try {
            $service->executeParsing($organization);

            $parsingJob->update([
                'status' => 'completed',
                'processed' => $organization->reviews()->count(),
                'total' => $organization->review_count,
            ]);
        } catch (RateLimitException $e) {
            $parsingJob->update([
                'status' => 'failed',
                'error' => "Превышен лимит: {$e->retryAfterSeconds}с",
                'attempts' => $this->attempts(),
            ]);
            $this->release($e->retryAfterSeconds);
        } catch (BlockedException $e) {
            $parsingJob->update([
                'status' => 'failed',
                'error' => 'Заблокировано',
                'attempts' => $this->attempts(),
            ]);
            $this->release(300);
        } catch (InvalidRequestException $e) {
            $parsingJob->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);
            $this->fail($e);
        } catch (LayoutChangedException $e) {
            $parsingJob->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);
            $this->fail($e);
        } catch (\Throwable $e) {
            $parsingJob->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]);
            throw $e;
        }
    }

    /**
     * Обработка окончательного провала.
     * @param \Throwable|null $exception Исключение
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Parse job permanently failed', [
            'organization_id' => $this->organizationId,
            'error' => $exception?->getMessage(),
        ]);

        $orgRepo = app(OrganizationRepositoryInterface::class);
        $organization = $orgRepo->findById($this->organizationId);

        if (!$organization) {
            return;
        }

        if ($organization->status === 'requires_attention') {
            return;
        }

        $orgRepo->update($organization, [
            'status' => 'error',
            'last_error' => $exception?->getMessage() ?? 'Неизвестная ошибка',
        ]);
    }
}
