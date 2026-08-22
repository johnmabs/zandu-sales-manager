<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\Store;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

interface StoreRepository
{
    public function save(Store $store): void;

    /** @throws StoreNotFound */
    public function get(OrganizationId $organizationId, StoreId $storeId): Store;

    public function find(OrganizationId $organizationId, StoreId $storeId): ?Store;

    /** @return list<Store> */
    public function findAll(OrganizationId $organizationId): array;

    public function codeExists(OrganizationId $organizationId, StoreCode $code): bool;
}
