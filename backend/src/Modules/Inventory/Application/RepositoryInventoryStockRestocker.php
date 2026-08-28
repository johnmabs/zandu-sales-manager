<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Application\Contract\{InventoryStockRestocker, RestockSaleReturn, RestockedStockItem, StockRestockResult};
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementSource, StockMovementType};
use Zandu\SharedKernel\Identity\{IdGenerator, StockMovementId};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Time\Clock;

final readonly class RepositoryInventoryStockRestocker implements InventoryStockRestocker
{
    public function __construct(
        private StockRepository $stocks,
        private StockMovementRepository $movements,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function restockSaleReturn(RestockSaleReturn $request): StockRestockResult
    {
        /** @var array<string, array{productId: \Zandu\SharedKernel\Identity\ProductId, quantity: Quantity}> $items */
        $items = [];
        foreach ($request->items as $item) {
            $key = $item['productId']->toString();
            $items[$key] = isset($items[$key])
                ? ['productId' => $item['productId'], 'quantity' => $items[$key]['quantity']->add($item['baseQuantity'])]
                : ['productId' => $item['productId'], 'quantity' => $item['baseQuantity']];
        }
        ksort($items);

        $alreadyRestocked = true;
        $restocked = [];
        foreach ($items as $item) {
            $stock = $this->stocks->getForUpdate($request->organizationId, $request->storeId, $item['productId']);
            $quantity = new MovementQuantity($item['quantity']);
            $movement = StockMovement::record(
                StockMovementId::generate($this->ids),
                $request->organizationId,
                $request->storeId,
                $item['productId'],
                $stock->id(),
                StockMovementType::SaleReturn,
                $quantity,
                $stock->quantityOnHand(),
                StockMovementSource::saleReturn($request->returnSaleId),
                null,
                $request->actorContext->actorId(),
                $this->clock->now(),
            );
            if (!$this->movements->appendOnce($movement)) {
                continue;
            }

            $alreadyRestocked = false;
            $stock->increase($quantity);
            $this->stocks->save($stock);
            $restocked[] = new RestockedStockItem(
                $item['productId'],
                $stock->id(),
                $movement->id(),
                $quantity->value(),
                $movement->previousQuantity()->value(),
                $movement->resultingQuantity()->value(),
                $movement->occurredAt(),
            );
        }

        return new StockRestockResult($request->returnSaleId, $alreadyRestocked, $restocked);
    }
}
