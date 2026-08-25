<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\ActivateUnitOfMeasure;

use Zandu\Modules\Catalog\Application\TenantUnitOfMeasureLoader;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ActivateUnitOfMeasureHandler
{
    public function __construct(
        private TenantUnitOfMeasureLoader $loader,
        private UnitOfMeasureRepository $units,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
    ) {}

    public function __invoke(ActivateUnitOfMeasure $command): UnitOfMeasure
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): UnitOfMeasure {
            $this->authorization->authorize($command->actorContext, PermissionCode::UnitOfMeasureActivate, ResourceScope::organization($organizationId));
            $unit = $this->loader->get($command->unitId, $command->actorContext);
            $unit->activate($command->actorContext->actorId(), $this->clock->now());
            $this->units->save($unit);

            return $unit;
        });
    }
}
