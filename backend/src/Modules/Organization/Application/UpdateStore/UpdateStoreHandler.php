<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\UpdateStore;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateStoreHandler
{
    public function __construct(
        private TenantStoreLoader $loader,
        private StoreRepository $stores,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
    ) {}

    public function __invoke(UpdateStore $command): Store
    {
        return $this->transaction->transactional($command->actorContext->organizationId(), function () use ($command): Store {
            $store = $this->loader->get($command->storeId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::StoreUpdate, ResourceScope::store($store->organizationId(), $store->id()));
            $this->operationalGuard->assertStore($command->actorContext, $store->id());
            $now = $this->clock->now();
            $store->update(
                StoreName::fromString($command->name),
                null !== $command->address ? StoreAddress::fromString($command->address) : null,
                TimeZone::fromString($command->timeZone),
                Locale::fromString($command->locale),
                $command->actorContext->actorId(),
                $now,
            );
            $this->stores->save($store);
            $this->audit->recordSuccess($command->actorContext, SecurityAction::StoreUpdated, ResourceReference::for('store', $store->id()), SafeAuditMetadata::empty(), $now);

            return $store;
        });
    }
}
