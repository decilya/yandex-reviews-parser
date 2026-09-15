<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Organization\Contracts\OrganizationRepositoryInterface;
use App\Domain\Review\Contracts\ReviewRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер отзывов.
 */
class ReviewController extends Controller
{
    /**
     * @param ReviewRepositoryInterface $reviewRepo Репозиторий отзывов
     * @param OrganizationRepositoryInterface $orgRepo Репозиторий организаций
     */
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepo,
        private readonly OrganizationRepositoryInterface $orgRepo,
    ) {
    }

    /**
     * Список отзывов.
     * @param Request $request Запрос
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $organization = $this->orgRepo->findByUser($request->user()->id);

        if (!$organization) {
            return response()->json(['message' => 'Организация не найдена'], 404);
        }

        $requestedPerPage = (int) $request->get('per_page', 50);
        $perPage = min(max($requestedPerPage, 1), 50);

        $reviews = $this->reviewRepo->paginateByOrganization($organization->id, $perPage);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'organization' => [
                'name' => $organization->name,
                'rating' => $organization->rating,
                'rating_count' => $organization->rating_count,
                'review_count' => $organization->review_count,
            ],
        ]);
    }
}
