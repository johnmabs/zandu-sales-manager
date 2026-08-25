<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\DeactivateUnitOfMeasure;

use Zandu\Modules\Catalog\Application\TenantUnitOfMeasureLoader;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class DeactivateUnitOfMeasureHandler
{
    public function __construct(
        private TenantUnitOfMeasureLoader $loader,
        private UnitOfMeasureRepository $units,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(DeactivateUnitOfMeasure $command): UnitOfMeasure
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): UnitOfMeasure {
            $this->authorization->authorize($command->actorContext, PermissionCode::UnitOfMeasureDeactivate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $unit = $this->loader->get($command->unitId, $command->actorContext);
            $unit->deactivate($command->actorContext->actorId(), $this->clock->now());
            $this->units->save($unit);

            return $unit;
        });
    }
}
