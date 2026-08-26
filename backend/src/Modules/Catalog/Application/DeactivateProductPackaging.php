<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class DeactivateProductPackaging
{
    public function __construct(
        public ProductPackagingId $packagingId,
        public ActorContext $actorContext,
    ) {}
}
