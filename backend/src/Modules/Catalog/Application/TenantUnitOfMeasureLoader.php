<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class TenantUnitOfMeasureLoader
{
    public function __construct(private UnitOfMeasureRepository $units) {}

    public function get(UnitOfMeasureId $unitId, ActorContext $actorContext): UnitOfMeasure
    {
        return $this->units->get($actorContext->organizationId(), $unitId);
    }
}
