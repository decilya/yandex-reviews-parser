<?php
// app/Domain/Organization/Contracts/OrganizationRepositoryInterface.php
// Репозиторий организаций.

declare(strict_types=1);

namespace App\Domain\Organization\Contracts;

use App\Domain\Organization\Models\Organization;

interface OrganizationRepositoryInterface
{
    public function findByUser(int $userId): ?Organization;
    public function findById(int $id): ?Organization;
    public function create(array $data): Organization;
    public function update(Organization $organization, array $data): Organization;
}
