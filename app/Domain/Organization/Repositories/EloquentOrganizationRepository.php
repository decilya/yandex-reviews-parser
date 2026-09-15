<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repositories;

use App\Domain\Organization\Contracts\OrganizationRepositoryInterface;
use App\Domain\Organization\Models\Organization;

/**
 * Репозиторий организаций.
 */
class EloquentOrganizationRepository implements OrganizationRepositoryInterface
{
    /**
     * Найти по пользователю.
     * @param int $userId ID
     * @return Organization|null
     */
    public function findByUser(int $userId): ?Organization
    {
        return Organization::where('user_id', $userId)->first();
    }

    /**
     * Найти по ID.
     * @param int $id ID
     * @return Organization|null
     */
    public function findById(int $id): ?Organization
    {
        return Organization::find($id);
    }

    /**
     * Создать.
     * @param array<string, mixed> $data Данные
     * @return Organization
     */
    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    /**
     * Обновить.
     * @param Organization $organization Модель
     * @param array<string, mixed> $data Данные
     * @return Organization
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);
        return $organization->refresh();
    }
}
