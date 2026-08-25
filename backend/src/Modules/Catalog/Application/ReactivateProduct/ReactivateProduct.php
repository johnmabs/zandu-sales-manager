<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\ReactivateProduct;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;

final readonly class ReactivateProduct
{
    public function __construct(
        public ProductId $productId,
        public ActorContext $actorContext,
    ) {}
}
