<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\CancelStoreClosure;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CancelStoreClosureHandler
{
    public function __construct(
        private TenantStoreLoader $loader,
        private StoreRepository $stores,
        private StoreClosureRepository $closures,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CancelStoreClosure $command): Store
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Store {
            $store = $this->loader->get($command->storeId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::StoreClose, ResourceScope::store($store->organizationId(), $store->id()));
            $this->operationalGuard->assertStore($command->actorContext, $store->id(), OperationalMode::Remediation);
            $closure = $this->closures->getActiveForStore($organizationId, $store->id());
            $closure->cancel();
            $store->cancelClosure($command->actorContext->actorId(), $this->clock->now());
            $this->closures->save($closure);
            $this->stores->save($store);

            return $store;
        });
    }
}
