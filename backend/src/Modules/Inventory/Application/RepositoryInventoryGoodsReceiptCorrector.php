<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Application\Contract\ApplyGoodsReceiptCorrection;
use Zandu\Modules\Inventory\Application\Contract\InventoryGoodsReceiptCorrector;
use Zandu\Modules\Inventory\Domain\Stock\MovementQuantity;
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovement;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementRepository;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementSource;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementType;
use Zandu\Modules\InventoryCosting\Application\Contract\InventoryCostingMovementType;
use Zandu\Modules\InventoryCosting\Application\Contract\InventoryMovementValuer;
use Zandu\Modules\InventoryCosting\Application\Contract\ValueInventoryMovement;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\StockMovementId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class RepositoryInventoryGoodsReceiptCorrector implements InventoryGoodsReceiptCorrector
{
    public function __construct(private StockRepository $stocks, private StockMovementRepository $movements, private InventoryMovementValuer $costing, private IdGenerator $ids, private DecimalFactory $decimals) {}

    public function apply(ApplyGoodsReceiptCorrection $correction): int
    {
        $items = $correction->items;
        usort($items, static fn(array $left, array $right): int => $left['productId']->toString() <=> $right['productId']->toString());
        $applied = 0;
        foreach ($items as $item) {
            if ($item['difference']->isZero()) {
                continue;
            }
            $incoming = !$item['difference']->isNegative();
            $quantity = new MovementQuantity($this->absolute($item['difference']));
            $stock = $this->stocks->getForUpdate($correction->organizationId, $correction->storeId, $item['productId']);
            $previous = $stock->quantityOnHand();
            $type = $incoming ? StockMovementType::GoodsReceiptCorrectionIn : StockMovementType::GoodsReceiptCorrectionOut;
            $movement = StockMovement::record(StockMovementId::generate($this->ids), $correction->organizationId, $correction->storeId, $item['productId'], $stock->id(), $type, $quantity, $previous, StockMovementSource::goodsReceiptCorrection($correction->correctionId), $correction->reason, $correction->actorContext->actorId(), $correction->occurredAt);
            if (!$this->movements->appendOnce($movement)) {
                throw new \LogicException('Goods receipt correction stock was already applied while the correction is still draft.');
            }
            $incoming ? $stock->increase($quantity) : $stock->decrease($quantity);
            $this->stocks->save($stock);
            $this->costing->value(new ValueInventoryMovement($correction->storeId, $item['productId'], $stock->id(), $movement->id(), $incoming ? InventoryCostingMovementType::GoodsReceiptCorrectionIn : InventoryCostingMovementType::GoodsReceiptCorrectionOut, $quantity->value(), $movement->previousQuantity()->value(), $movement->resultingQuantity()->value(), $incoming ? $item['incomingUnitCost']->amount() : null, $correction->correctionId->toString(), $correction->occurredAt, $correction->actorContext));
            ++$applied;
        }
        return $applied;
    }

    private function absolute(Quantity $quantity): Quantity
    {
        return $quantity->isNegative() ? Quantity::fromString(ltrim($quantity->toString(), '-'), $this->decimals) : $quantity;
    }
}
