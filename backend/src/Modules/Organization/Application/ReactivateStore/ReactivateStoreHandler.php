<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\ReactivateStore;

use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ReactivateStoreHandler
{
    public function __construct(private TenantStoreLoader $loader, private StoreRepository $stores, private Clock $clock, private TenantTransaction $transaction) {}

    public function __invoke(ReactivateStore $command): Store
    {
        return $this->transaction->transactional($command->actorContext->organizationId(), function () use ($command): Store {
            $store = $this->loader->get($command->storeId, $command->actorContext);
            $store->reactivate($command->actorContext->actorId(), $this->clock->now());
            $this->stores->save($store);

            return $store;
        });
    }
}
