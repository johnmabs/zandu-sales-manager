<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use LogicException;
use Zandu\Modules\Inventory\Application\Contract\InventoryPurchaseReturnShipper;
use Zandu\Modules\Inventory\Application\Contract\PurchaseReturnStockUnavailable;
use Zandu\Modules\Inventory\Application\Contract\ShipPurchaseReturnStock;
use Zandu\Modules\Inventory\Domain\Stock\MovementQuantity;
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovement;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementRepository;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementSource;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementType;
use Zandu\Modules\InventoryCosting\Application\Contract\InventoryCostingMovementType;
use Zandu\Modules\InventoryCosting\Application\Contract\InventoryMovementValuer;
use Zandu\Modules\InventoryCosting\Application\Contract\ValueInventoryMovement;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\StockMovementId;

final readonly class RepositoryInventoryPurchaseReturnShipper implements InventoryPurchaseReturnShipper
{
    public function __construct(
        private StockRepository $stocks,
        private StockMovementRepository $movements,
        private InventoryMovementValuer $costing,
        private IdGenerator $ids,
    ) {}

    public function ship(ShipPurchaseReturnStock $request): int
    {
        $items = $request->items;
        usort($items, static fn(array $left, array $right): int => $left['productId']->toString() <=> $right['productId']->toString());
        $shipped = 0;

        foreach ($items as $item) {
            $stock = $this->stocks->getForUpdate($request->organizationId, $request->storeId, $item['productId']);
            if ($stock->quantityOnHand()->value()->compareTo($item['baseQuantity']) < 0) {
                throw new PurchaseReturnStockUnavailable($item['productId']);
            }

            $quantity = new MovementQuantity($item['baseQuantity']);
            $movement = StockMovement::record(
                StockMovementId::generate($this->ids),
                $request->organizationId,
                $request->storeId,
                $item['productId'],
                $stock->id(),
                StockMovementType::PurchaseReturn,
                $quantity,
                $stock->quantityOnHand(),
                StockMovementSource::purchaseReturn($request->purchaseReturnId),
                $request->reason,
                $request->actorContext->actorId(),
                $request->occurredAt,
            );
            if (!$this->movements->appendOnce($movement)) {
                throw new LogicException('Purchase return stock was already shipped while the document is still draft.');
            }

            $stock->decrease($quantity);
            $this->stocks->save($stock);
            $this->costing->value(new ValueInventoryMovement(
                $request->storeId,
                $item['productId'],
                $stock->id(),
                $movement->id(),
                InventoryCostingMovementType::PurchaseReturn,
                $quantity->value(),
                $movement->previousQuantity()->value(),
                $movement->resultingQuantity()->value(),
                null,
                $request->purchaseReturnId->toString(),
                $request->occurredAt,
                $request->actorContext,
            ));
            ++$shipped;
        }

        return $shipped;
    }
}
