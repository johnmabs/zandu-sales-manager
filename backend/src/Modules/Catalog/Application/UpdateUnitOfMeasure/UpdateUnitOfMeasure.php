<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\UpdateUnitOfMeasure;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class UpdateUnitOfMeasure
{
    public function __construct(
        public UnitOfMeasureId $unitId,
        public string $name,
        public string $dimension,
        public int $precision,
        public string $roundingMode,
        public ActorContext $actorContext,
    ) {}
}
