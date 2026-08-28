<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use LogicException;
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale, CostedStockConsumption, InventoryStockConsumer, StockConsumptionResult};
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementSource, StockMovementType};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement};
use Zandu\SharedKernel\Identity\{IdGenerator, StockMovementId};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Time\Clock;

final readonly class RepositoryInventoryStockConsumer implements InventoryStockConsumer
{
    public function __construct(
        private StockRepository $stocks,
        private StockMovementRepository $movements,
        private InventoryMovementValuer $costing,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function consumeStockForSale(ConsumeStockForSale $request): StockConsumptionResult
    {
        /** @var array<string, array{productId: \Zandu\SharedKernel\Identity\ProductId, quantity: Quantity}> $items */
        $items = [];
        foreach ($request->items as $item) {
            $key = $item['productId']->toString();
            $items[$key] = isset($items[$key])
                ? ['productId' => $item['productId'], 'quantity' => $items[$key]['quantity']->add($item['baseQuantity'])]
                : ['productId' => $item['productId'], 'quantity' => $item['baseQuantity']];
        }

        $alreadyConsumed = true;
        $costedItems = [];
        foreach ($items as $item) {
            $stock = $this->stocks->get($request->organizationId, $request->storeId, $item['productId']);
            $quantity = new MovementQuantity($item['quantity']);
            $movement = StockMovement::record(
                StockMovementId::generate($this->ids),
                $request->organizationId,
                $request->storeId,
                $item['productId'],
                $stock->id(),
                StockMovementType::Sale,
                $quantity,
                $stock->quantityOnHand(),
                StockMovementSource::sale($request->saleId),
                null,
                null,
                $this->clock->now(),
            );
            if (!$this->movements->appendOnce($movement)) {
                continue;
            }
            $alreadyConsumed = false;
            if (!$this->stocks->decreaseIfAvailable($request->organizationId, $stock->id(), $quantity, $stock->version())) {
                throw new LogicException('Insufficient stock or concurrent stock modification.');
            }
            $valued = $this->costing->value(new ValueInventoryMovement(
                $request->storeId,
                $item['productId'],
                $stock->id(),
                $movement->id(),
                InventoryCostingMovementType::Sale,
                $quantity->value(),
                $movement->previousQuantity()->value(),
                $movement->resultingQuantity()->value(),
                null,
                $request->saleId->toString(),
                $movement->occurredAt(),
                $request->actorContext,
            ));
            $costedItems[] = new CostedStockConsumption(
                $item['productId'],
                $valued->stockId,
                $valued->stockMovementId,
                $valued->quantity,
                $valued->unitCost,
                $valued->totalCost,
                $valued->valuationVersion,
                $valued->occurredAt,
            );
        }

        return new StockConsumptionResult($request->saleId, $alreadyConsumed, $costedItems);
    }
}
