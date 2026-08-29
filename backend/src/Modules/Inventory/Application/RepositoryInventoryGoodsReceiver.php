<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Application\Contract\{GoodsReceiptStockResult, InventoryGoodsReceiver, ReceiveSupplierGoods, ReceivedGoodsItem};
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementSource, StockMovementType};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement};
use Zandu\SharedKernel\Identity\{IdGenerator, StockMovementId};

final readonly class RepositoryInventoryGoodsReceiver implements InventoryGoodsReceiver
{
    public function __construct(
        private StockRepository $stocks,
        private StockMovementRepository $movements,
        private InventoryMovementValuer $costing,
        private IdGenerator $ids,
    ) {}

    public function receive(ReceiveSupplierGoods $request): GoodsReceiptStockResult
    {
        $items = $request->items;
        usort($items, static fn(array $left, array $right): int => $left['productId']->toString() <=> $right['productId']->toString());
        $alreadyReceived = true;
        $received = [];
        foreach ($items as $item) {
            $stock = $this->stocks->getForUpdate($request->organizationId, $request->storeId, $item['productId']);
            $quantity = new MovementQuantity($item['baseQuantity']);
            $movement = StockMovement::record(
                StockMovementId::generate($this->ids),
                $request->organizationId,
                $request->storeId,
                $item['productId'],
                $stock->id(),
                StockMovementType::PurchaseReceipt,
                $quantity,
                $stock->quantityOnHand(),
                StockMovementSource::goodsReceipt($request->goodsReceiptId),
                null,
                $request->actorContext->actorId(),
                $request->receivedAt,
            );
            if (!$this->movements->appendOnce($movement)) {
                continue;
            }

            $alreadyReceived = false;
            $stock->increase($quantity);
            $this->stocks->save($stock);
            $this->costing->value(new ValueInventoryMovement(
                $request->storeId,
                $item['productId'],
                $stock->id(),
                $movement->id(),
                InventoryCostingMovementType::PurchaseReceipt,
                $quantity->value(),
                $movement->previousQuantity()->value(),
                $movement->resultingQuantity()->value(),
                $item['inventoryUnitCost']->amount(),
                $request->goodsReceiptId->toString(),
                $request->receivedAt,
                $request->actorContext,
            ));
            $received[] = new ReceivedGoodsItem(
                $item['productId'],
                $stock->id(),
                $movement->id(),
                $quantity->value(),
                $movement->previousQuantity()->value(),
                $movement->resultingQuantity()->value(),
            );
        }

        return new GoodsReceiptStockResult($request->goodsReceiptId, $alreadyReceived, $received);
    }
}
