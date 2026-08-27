<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockMovement;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity,StockQuantity};
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,ProductId,StockId,StockMovementId,StoreId};

final readonly class StockMovement
{
    private function __construct(private StockMovementId $id, private OrganizationId $organizationId, private StoreId $storeId, private ProductId $productId, private StockId $stockId, private StockMovementType $type, private MovementQuantity $quantity, private StockQuantity $previousQuantity, private StockQuantity $resultingQuantity, private StockMovementSource $source, private ?string $reason, private ?ActorId $performedBy, private DateTimeImmutable $occurredAt)
    {
        self::assertConsistency($type, $previousQuantity, $resultingQuantity, $quantity);
    }
    public static function record(StockMovementId $id, OrganizationId $organizationId, StoreId $storeId, ProductId $productId, StockId $stockId, StockMovementType $type, MovementQuantity $quantity, StockQuantity $previousQuantity, StockMovementSource $source, ?string $reason, ?ActorId $performedBy, DateTimeImmutable $occurredAt): self
    {
        $result = $type->isIncrease() ? $previousQuantity->value()->add($quantity->value()) : $previousQuantity->value()->subtract($quantity->value());
        return new self($id, $organizationId, $storeId, $productId, $stockId, $type, $quantity, $previousQuantity, new StockQuantity($result), $source, $reason, $performedBy, $occurredAt->setTimezone(new DateTimeZone('UTC')));
    }
    private static function assertConsistency(StockMovementType $type, StockQuantity $previous, StockQuantity $result, MovementQuantity $quantity): void
    {
        $expected = $type->isIncrease() ? $previous->value()->add($quantity->value()) : $previous->value()->subtract($quantity->value());
        if (!$expected->equals($result->value())) {
            throw new LogicException('Stock movement quantities are inconsistent.');
        }
    }
    public function id(): StockMovementId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function storeId(): StoreId
    {
        return $this->storeId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function stockId(): StockId
    {
        return $this->stockId;
    }
    public function type(): StockMovementType
    {
        return $this->type;
    }
    public function quantity(): MovementQuantity
    {
        return $this->quantity;
    }
    public function previousQuantity(): StockQuantity
    {
        return $this->previousQuantity;
    }
    public function resultingQuantity(): StockQuantity
    {
        return $this->resultingQuantity;
    }
    public function source(): StockMovementSource
    {
        return $this->source;
    }
    public function reason(): ?string
    {
        return $this->reason;
    }
    public function performedBy(): ?ActorId
    {
        return $this->performedBy;
    }
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
