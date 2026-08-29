<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshotProvider;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class PurchaseOrderLineFactory
{
    public function __construct(
        private PurchasableProductSnapshotProvider $catalog,
        private IdGenerator $idGenerator,
        private DecimalFactory $decimals,
    ) {}

    public function create(
        PurchaseOrder $order,
        ProductId $productId,
        ?ProductPackagingId $packagingId,
        Quantity $enteredQuantity,
        Money $unitCost,
        ?PurchaseOrderLineId $lineId = null,
    ): PurchaseOrderLine {
        $snapshot = $this->catalog->provide($order->organizationId(), $productId, $packagingId);
        $conversionFactor = new Quantity($snapshot->conversionFactor);

        return new PurchaseOrderLine(
            $lineId ?? PurchaseOrderLineId::generate($this->idGenerator),
            $order->id(),
            $productId,
            $packagingId,
            $enteredQuantity,
            $conversionFactor,
            $enteredQuantity->multiply($snapshot->conversionFactor, 12, RoundingMode::HalfEven),
            $unitCost,
            $unitCost->divide($snapshot->conversionFactor, 12, RoundingMode::HalfEven),
            Quantity::fromString('0', $this->decimals),
        );
    }
}
