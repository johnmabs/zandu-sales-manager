<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class CreateProductPackaging
{
    public function __construct(
        public ProductId $productId,
        public string $code,
        public string $name,
        public UnitOfMeasureId $unitId,
        public string $conversionFactor,
        public int $precision,
        public string $minimumQuantity,
        public string $quantityIncrement,
        public bool $allowedForSale,
        public bool $allowedForPurchase,
        public ActorContext $actorContext,
    ) {}
}
