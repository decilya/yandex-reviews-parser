<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс организации.
 */
class OrganizationResource extends JsonResource
{
    /**
     * Преобразование в массив.
     * @param Request $request Запрос
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'yandex_url' => $this->yandex_url,
            'rating' => $this->rating,
            'rating_count' => $this->rating_count,
            'review_count' => $this->review_count,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'last_parsed_at' => $this->last_parsed_at?->toIso8601String(),
        ];
    }
}
