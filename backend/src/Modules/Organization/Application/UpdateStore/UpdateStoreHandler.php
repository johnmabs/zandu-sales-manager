<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\UpdateStore;

use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateStoreHandler
{
    public function __construct(
        private TenantStoreLoader $loader,
        private StoreRepository $stores,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(UpdateStore $command): Store
    {
        return $this->transaction->transactional($command->actorContext->organizationId(), function () use ($command): Store {
            $store = $this->loader->get($command->storeId, $command->actorContext);
            $store->update(
                StoreName::fromString($command->name),
                null !== $command->address ? StoreAddress::fromString($command->address) : null,
                TimeZone::fromString($command->timeZone),
                Locale::fromString($command->locale),
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->stores->save($store);

            return $store;
        });
    }
}
