<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\DeactivateUnitOfMeasure;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class DeactivateUnitOfMeasure
{
    public function __construct(
        public UnitOfMeasureId $unitId,
        public ActorContext $actorContext,
    ) {}
}
