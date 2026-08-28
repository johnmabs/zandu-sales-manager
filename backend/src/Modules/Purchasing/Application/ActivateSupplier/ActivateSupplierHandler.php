<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ActivateSupplier;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ActivateSupplierHandler
{
    public function __construct(
        private TenantSupplierLoader $loader,
        private SupplierRepository $suppliers,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(ActivateSupplier $command): Supplier
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Supplier {
            $this->authorization->authorize($command->actorContext, PermissionCode::SupplierUpdate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $supplier = $this->loader->get($command->supplierId, $command->actorContext);
            $supplier->activate($command->actorContext->actorId(), $this->clock->now());
            $this->suppliers->save($supplier);

            return $supplier;
        });
    }
}
