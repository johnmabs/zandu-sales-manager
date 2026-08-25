<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\ArchiveProduct;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;

final readonly class ArchiveProduct
{
    public function __construct(
        public ProductId $productId,
        public ActorContext $actorContext,
    ) {}
}
