<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс статуса парсинга.
 */
class ParsingStatusResource extends JsonResource
{
    /**
     * Преобразование в массив.
     * @param Request $request Запрос
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'progress' => $this->progress_percent,
            'processed' => $this->processed,
            'total' => $this->total,
            'error' => $this->error,
        ];
    }
}
