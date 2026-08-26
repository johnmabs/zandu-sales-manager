<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\Contract;

use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class InventoryProductDescriptor
{
    public function __construct(
        private ProductId $productId,
        private bool $inventoryTracked,
        private string $productType,
        private UnitOfMeasureId $baseUnitId,
        private int $quantityPrecision,
    ) {}

    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function inventoryTracked(): bool
    {
        return $this->inventoryTracked;
    }
    public function productType(): string
    {
        return $this->productType;
    }
    public function baseUnitId(): UnitOfMeasureId
    {
        return $this->baseUnitId;
    }
    public function quantityPrecision(): int
    {
        return $this->quantityPrecision;
    }
}
