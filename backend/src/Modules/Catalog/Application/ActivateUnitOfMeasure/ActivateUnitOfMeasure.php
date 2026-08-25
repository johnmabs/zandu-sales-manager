<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\ActivateUnitOfMeasure;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class ActivateUnitOfMeasure
{
    public function __construct(
        public UnitOfMeasureId $unitId,
        public ActorContext $actorContext,
    ) {}
}
