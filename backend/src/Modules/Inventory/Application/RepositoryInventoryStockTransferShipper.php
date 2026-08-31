<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use LogicException;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockTransferShipper, ShipStockTransferStock, StockTransferStockUnavailable};
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity,StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement,StockMovementRepository,StockMovementSource,StockMovementType};
use Zandu\SharedKernel\Identity\{IdGenerator,StockMovementId};

final readonly class RepositoryInventoryStockTransferShipper implements InventoryStockTransferShipper
{
    public function __construct(private StockRepository $stocks, private StockMovementRepository $movements, private IdGenerator $ids) {} public function ship(ShipStockTransferStock $request): int
    {
        $items = $request->items;
        usort($items, static fn(array $a, array $b): int => $a['productId']->toString() <=> $b['productId']->toString());
        foreach ($items as $item) {
            $stock = $this->stocks->getForUpdate($request->organizationId, $request->sourceStoreId, $item['productId']);
            if ($stock->quantityOnHand()->value()->compareTo($item['baseQuantity']) < 0) {
                throw new StockTransferStockUnavailable($item['productId']);
            }$q = new MovementQuantity($item['baseQuantity']);
            $m = StockMovement::record(StockMovementId::generate($this->ids), $request->organizationId, $request->sourceStoreId, $item['productId'], $stock->id(), StockMovementType::TransferOut, $q, $stock->quantityOnHand(), StockMovementSource::stockTransfer($request->transferId), null, $request->actorContext->actorId(), $request->occurredAt);
            if (!$this->movements->appendOnce($m)) {
                throw new LogicException('Stock transfer was already shipped.');
            }$stock->decrease($q);
            $this->stocks->save($stock);
        }return count($items);
    }
}
