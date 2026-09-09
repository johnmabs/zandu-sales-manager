<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Application\Contract\OrganizationStoreIds;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class RepositoryOrganizationStoreIds implements OrganizationStoreIds
{
    public function __construct(private StoreRepository $stores) {}

    /** @return list<StoreId> */
    public function forOrganization(OrganizationId $organizationId): array
    {
        return array_map(static fn(Store $store): StoreId => $store->id(), $this->stores->findAll($organizationId));
    }
}
