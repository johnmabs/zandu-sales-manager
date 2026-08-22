<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\ReactivateStore;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ReactivateStoreHandler
{
    public function __construct(private TenantStoreLoader $loader, private StoreRepository $stores, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $operationalGuard, private SecurityAuditTrail $audit) {}

    public function __invoke(ReactivateStore $command): Store
    {
        return $this->transaction->transactional($command->actorContext->organizationId(), function () use ($command): Store {
            $store = $this->loader->get($command->storeId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::StoreSuspend, ResourceScope::store($store->organizationId(), $store->id()));
            $this->operationalGuard->assertStore($command->actorContext, $store->id(), OperationalMode::Remediation);
            $now = $this->clock->now();
            $store->reactivate($command->actorContext->actorId(), $now);
            $this->stores->save($store);
            $this->audit->recordSuccess($command->actorContext, SecurityAction::StoreReactivated, ResourceReference::for('store', $store->id()), SafeAuditMetadata::empty(), $now);

            return $store;
        });
    }
}
