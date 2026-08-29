<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshotProvider;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class GoodsReceiptLineFactory
{
    public function __construct(
        private PurchasableProductSnapshotProvider $catalog,
        private IdGenerator $idGenerator,
    ) {}

    public function createDirect(
        OrganizationId $organizationId,
        GoodsReceiptId $goodsReceiptId,
        ProductId $productId,
        ?ProductPackagingId $packagingId,
        Quantity $enteredReceivedQuantity,
        Money $inventoryUnitCost,
    ): GoodsReceiptLine {
        $snapshot = $this->catalog->provide($organizationId, $productId, $packagingId);
        $conversionFactor = new Quantity($snapshot->conversionFactor);

        return new GoodsReceiptLine(
            GoodsReceiptLineId::generate($this->idGenerator),
            $goodsReceiptId,
            $productId,
            $packagingId,
            $enteredReceivedQuantity,
            $conversionFactor,
            $enteredReceivedQuantity->multiply($snapshot->conversionFactor, 12, RoundingMode::HalfEven),
            null,
            $inventoryUnitCost,
            null,
        );
    }

    public function createLinked(
        GoodsReceiptId $goodsReceiptId,
        PurchaseOrderLine $purchaseOrderLine,
        Quantity $enteredReceivedQuantity,
        ?Money $actualUnitCost,
    ): GoodsReceiptLine {
        $conversionFactor = $purchaseOrderLine->conversionFactorSnapshot();
        $inventoryUnitCost = null === $actualUnitCost
            ? $purchaseOrderLine->inventoryUnitCost()
            : $actualUnitCost->divide($conversionFactor->value(), 12, RoundingMode::HalfEven);

        return new GoodsReceiptLine(
            GoodsReceiptLineId::generate($this->idGenerator),
            $goodsReceiptId,
            $purchaseOrderLine->productId(),
            $purchaseOrderLine->productPackagingId(),
            $enteredReceivedQuantity,
            $conversionFactor,
            $enteredReceivedQuantity->multiply($conversionFactor->value(), 12, RoundingMode::HalfEven),
            $actualUnitCost,
            $inventoryUnitCost,
            $purchaseOrderLine->id(),
        );
    }
}
