<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class TenantStoreLoader
{
    public function __construct(private StoreRepository $stores) {}

    public function get(StoreId $storeId, ActorContext $actorContext): Store
    {
        return $this->stores->get($actorContext->organizationId(), $storeId);
    }
}
