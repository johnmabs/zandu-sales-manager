<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class UpdateProductPackaging
{
    public function __construct(
        public ProductPackagingId $packagingId,
        public string $name,
        public string $minimumQuantity,
        public string $quantityIncrement,
        public bool $allowedForSale,
        public bool $allowedForPurchase,
        public ActorContext $actorContext,
    ) {}
}
