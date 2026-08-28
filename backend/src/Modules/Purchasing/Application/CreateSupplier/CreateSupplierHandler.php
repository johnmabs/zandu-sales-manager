<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateSupplier;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierName;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateSupplierHandler
{
    public function __construct(
        private SupplierRepository $suppliers,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreateSupplier $command): Supplier
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Supplier {
            $this->authorization->authorize($command->actorContext, PermissionCode::SupplierCreate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $supplier = Supplier::create(
                SupplierId::generate($this->idGenerator),
                $organizationId,
                SupplierName::fromString($command->name),
                $command->phone,
                $command->email,
                $command->address,
                $command->notes,
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->suppliers->save($supplier);

            return $supplier;
        });
    }
}
