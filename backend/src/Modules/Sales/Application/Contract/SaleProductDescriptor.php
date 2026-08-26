<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

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
    ) {}
}
