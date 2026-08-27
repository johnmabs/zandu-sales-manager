<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use LogicException;
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale, InventoryStockConsumer, StockConsumptionResult};
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementId, StockMovementRepository, StockMovementSource, StockMovementType};
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Time\Clock;

final readonly class RepositoryInventoryStockConsumer implements InventoryStockConsumer
{
    public function __construct(
        private StockRepository $stocks,
        private StockMovementRepository $movements,
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
        }

        return new StockConsumptionResult($request->saleId, $alreadyConsumed);
    }
}
