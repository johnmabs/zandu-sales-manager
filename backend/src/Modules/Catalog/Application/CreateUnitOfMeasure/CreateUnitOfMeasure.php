<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\CreateUnitOfMeasure;

use Zandu\SharedKernel\Context\ActorContext;

final readonly class CreateUnitOfMeasure
{
    public function __construct(
        public string $code,
        public string $name,
        public string $dimension,
        public int $precision,
        public string $roundingMode,
        public ActorContext $actorContext,
    ) {}
}
