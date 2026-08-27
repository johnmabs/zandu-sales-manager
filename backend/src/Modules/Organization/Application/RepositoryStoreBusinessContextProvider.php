<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Application\Contract\{StoreBusinessContext, StoreBusinessContextProvider};
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Identity\{OrganizationId, StoreId};

final readonly class RepositoryStoreBusinessContextProvider implements StoreBusinessContextProvider
{
    public function __construct(private StoreRepository $stores) {}

    public function provide(OrganizationId $organizationId, StoreId $storeId): StoreBusinessContext
    {
        $store = $this->stores->get($organizationId, $storeId);

        return new StoreBusinessContext($store->timeZone()->value(), $store->currency()->code());
    }
}
