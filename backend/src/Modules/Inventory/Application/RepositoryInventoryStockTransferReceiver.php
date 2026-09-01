<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use LogicException;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockTransferReceiver, ReceiveStockTransferStock, StockTransferStockResult};
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementSource, StockMovementType};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{IdGenerator, StockId, StockMovementId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class RepositoryInventoryStockTransferReceiver implements InventoryStockTransferReceiver
{
    public function __construct(private StockRepository $stocks, private StockMovementRepository $movements, private InventoryMovementValuer $costing, private IdGenerator $ids, private DecimalFactory $decimals) {} public function receive(ReceiveStockTransferStock $request): StockTransferStockResult
    {
        $items = $request->items;
        $costs = [];
        usort($items, static fn(array $a, array $b): int => $a['productId']->toString() <=> $b['productId']->toString());
        foreach ($items as $item) {
            $stock = $this->stocks->find($request->organizationId, $request->destinationStoreId, $item['productId']);
            if (null === $stock) {
                $stock = Stock::create(StockId::generate($this->ids), $request->organizationId, $request->destinationStoreId, $item['productId'], $this->zero());
                $stock->initialize($this->zero(), $request->actorContext->actorId(), $request->occurredAt);
            } else {
                $stock = $this->stocks->getForUpdate($request->organizationId, $request->destinationStoreId, $item['productId']);
            }
            $quantity = new MovementQuantity($item['baseQuantity']);
            $movement = StockMovement::record(StockMovementId::generate($this->ids), $request->organizationId, $request->destinationStoreId, $item['productId'], $stock->id(), StockMovementType::TransferIn, $quantity, $stock->quantityOnHand(), StockMovementSource::stockTransfer($request->transferId), null, $request->actorContext->actorId(), $request->occurredAt);
            $stock->increase($quantity);
            $this->stocks->save($stock);
            if (!$this->movements->appendOnce($movement)) {
                throw new LogicException('Stock transfer was already received.');
            }
            $valued = $this->costing->value(new ValueInventoryMovement($request->destinationStoreId, $item['productId'], $stock->id(), $movement->id(), InventoryCostingMovementType::TransferIn, $quantity->value(), $movement->previousQuantity()->value(), $movement->resultingQuantity()->value(), $item['incomingUnitCost'], $request->transferId->toString(), $request->occurredAt, $request->actorContext, true));
            $costs[$item['productId']->toString()] = ['unitCost' => $valued->unitCost, 'totalValue' => $valued->totalCost];
        }
        return new StockTransferStockResult(count($items), $costs);
    }

    private function zero(): StockQuantity
    {
        return new StockQuantity(Quantity::fromString('0', $this->decimals));
    }
}
