<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос сохранения организации.
 */
class SaveOrganizationRequest extends FormRequest
{
    /**
     * Авторизация.
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации.
     * @return array
     */
    public function rules(): array
    {
        return [
            'yandex_url' => [
                'required',
                'url',
                'regex:/^https?:\/\/(www\.)?(yandex|maps\.yandex)\.(ru|com|kz|by|uz|com\.tr)\/maps\//i',
            ],
        ];
    }

    /**
     * Сообщения об ошибках.
     * @return array
     */
    public function messages(): array
    {
        return [
            'yandex_url.required' => 'Ссылка обязательна',
            'yandex_url.url' => 'Некорректный URL',
            'yandex_url.regex' => 'Ссылка должна вести на Яндекс.Карты',
        ];
    }
}
