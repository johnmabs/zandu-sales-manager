<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductPriceId;

final readonly class DeactivateProductPrice
{
    public function __construct(
        public ProductPriceId $id,
        public ActorContext $actor,
    ) {}
}
