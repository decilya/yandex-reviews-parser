<?php

declare(strict_types=1);

namespace App\Domain\Parsing\Abstract;

use App\Domain\Organization\DTO\OrganizationDto;
use App\Domain\Parsing\Contracts\PageFetcherInterface;
use App\Domain\Parsing\Contracts\ReviewParserInterface;
use App\Domain\Parsing\DTO\ParsingResultDto;
use App\Domain\Parsing\Exceptions\EmptyResponseException;
use App\Domain\Parsing\Exceptions\LayoutChangedException;
use Illuminate\Support\Facades\Log;

/**
 * Абстрактный парсер с паттерном Template Method - единый алгоритм парсинга для любой площадки.
 */
abstract class AbstractPlatformParser implements ReviewParserInterface
{
    protected int $layoutFailureCount = 0;

    public function __construct(protected readonly PageFetcherInterface $fetcher)
    {
    }

    public function parse(string $url): ParsingResultDto
    {
        $this->resetState();
        $this->layoutFailureCount = 0;

        Log::info('Parsing started', ['url' => $url, 'parser' => static::class]);

        $rawContent = $this->fetchPage($url);
        $organizationDto = $this->extractOrganizationData($rawContent, $url);
        $reviews = $this->extractAllReviews($rawContent, $url);
        $this->validateResult($organizationDto, $reviews, $url);

        Log::info('Parsing completed', [
            'url' => $url,
            'reviews_count' => count($reviews),
            'rating' => $organizationDto->rating,
        ]);

        return new ParsingResultDto($organizationDto, $reviews);
    }

    protected function resetState(): void
    {
        // Хук для наследников
    }

    protected function fetchPage(string $url): string
    {
        $content = $this->fetcher->fetch($url, $this->getFetchOptions());

        if (empty($content)) {
            throw new EmptyResponseException($url);
        }

        $this->checkForBlock($content, $url);

        return $content;
    }

    abstract protected function extractOrganizationData(string $content, string $url): OrganizationDto;

    abstract protected function extractAllReviews(string $content, string $url): array;

    protected function getFetchOptions(): array
    {
        return [];
    }

    protected function checkForBlock(string $content, string $url): void
    {
    }

    protected function validateResult(OrganizationDto $org, array $reviews, string $url): void
    {
        if ($org->reviewCount > 0 && count($reviews) === 0) {
            throw new EmptyResponseException($url, [
                'expected_reviews' => $org->reviewCount,
                'received' => 0,
            ]);
        }
    }

    protected function handleLayoutFailure(string $missingField, string $url, array $context = []): void
    {
        $this->layoutFailureCount++;
        $maxFailures = (int) config('parsing.max_layout_failures', 3);

        Log::warning('Layout failure detected', [
            'url' => $url,
            'missing_field' => $missingField,
            'failure_count' => $this->layoutFailureCount,
            'max_failures' => $maxFailures,
            ...$context,
        ]);

        if ($this->layoutFailureCount >= $maxFailures) {
            throw new LayoutChangedException($url, $missingField, $context);
        }
    }
}
