<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\SuspendStore;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class SuspendStoreHandler
{
    public function __construct(private TenantStoreLoader $loader, private StoreRepository $stores, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $operationalGuard) {}

    public function __invoke(SuspendStore $command): Store
    {
        return $this->transaction->transactional($command->actorContext->organizationId(), function () use ($command): Store {
            $store = $this->loader->get($command->storeId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::StoreSuspend, ResourceScope::store($store->organizationId(), $store->id()));
            $this->operationalGuard->assertStore($command->actorContext, $store->id());
            $store->suspend($command->actorContext->actorId(), $this->clock->now());
            $this->stores->save($store);

            return $store;
        });
    }
}
