<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Services\OrganizationReviewService;
use App\Domain\Organization\Contracts\OrganizationRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\ParsingStatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер организаций.
 */
class OrganizationController extends Controller
{
    /**
     * @param OrganizationReviewService $service Сервис
     * @param OrganizationRepositoryInterface $orgRepo Репозиторий
     */
    public function __construct(
        private readonly OrganizationReviewService $service,
        private readonly OrganizationRepositoryInterface $orgRepo,
    ) {
    }

    /**
     * Показать организацию.
     * @param Request $request Запрос
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $organization = $this->orgRepo->findByUser($request->user()->id);

        if (!$organization) {
            return response()->json(['message' => 'Организация не найдена'], 404);
        }

        return response()->json(new OrganizationResource($organization));
    }

    /**
     * Сохранить организацию.
     * @param SaveOrganizationRequest $request Запрос
     * @return JsonResponse
     */
    public function store(SaveOrganizationRequest $request): JsonResponse
    {
        $organization = $this->service->saveOrganizationAndParse(
            $request->user()->id,
            $request->validated('yandex_url'),
        );

        return response()->json([
            'organization' => new OrganizationResource($organization),
            'message' => 'Парсинг запущен',
        ], 202);
    }

    /**
     * Статус парсинга.
     * @param Request $request Запрос
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $organization = $this->orgRepo->findByUser($request->user()->id);

        if (!$organization) {
            return response()->json(['message' => 'Организация не найдена'], 404);
        }

        $latestJob = $organization->latestJob();

        if (!$latestJob) {
            return response()->json([
                'status' => $organization->status,
                'progress' => 0,
                'processed' => 0,
                'total' => 0,
                'error' => null,
            ]);
        }

        return response()->json(new ParsingStatusResource($latestJob));
    }
}
