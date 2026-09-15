<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Модель пользователя.
 *
 * Стандартная модель Laravel с интеграцией Sanctum для API-аутентификации.
 *
 * @property int $id Идентификатор пользователя
 * @property string $name Имя пользователя
 * @property string $email Email пользователя
 * @property \Carbon\Carbon|null $email_verified_at Дата подтверждения email
 * @property string $password Хэш пароля
 * @property string|null $remember_token Токен "Запомнить меня"
 * @property \Carbon\Carbon|null $created_at Дата создания
 * @property \Carbon\Carbon|null $updated_at Дата обновления
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Атрибуты, доступные для массового присваивания.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Атрибуты, скрытые при сериализации модели.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Приведение типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
