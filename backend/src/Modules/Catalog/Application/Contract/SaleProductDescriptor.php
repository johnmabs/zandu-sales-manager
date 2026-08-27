<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\Contract;

use Zandu\SharedKernel\Identity\{ProductId,ProductPackagingId,UnitOfMeasureId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class SaleProductDescriptor
{
    public function __construct(
        public ProductId $productId,
        public ProductPackagingId $productPackagingId,
        public ?string $productCode,
        public ?string $productName,
        public string $packagingCode,
        public ?string $packagingName,
        public UnitOfMeasureId $unitId,
        public Quantity $conversionFactor,
        public int $sourceVersion,
        public bool $inventoryTracked = false,
        public string $productType = 'SERVICE',
        public ?Quantity $minimumQuantity = null,
        public ?Quantity $quantityIncrement = null,
        public int $quantityPrecision = 12,
    ) {}
}
