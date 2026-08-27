<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\StockValuationMovement;

#[ORM\Entity]
#[ORM\Table(name: 'stock_valuation_movement', schema: 'inventory_costing')]
final class StockValuationMovementRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $stockValuationId,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(type: 'guid')]
        private string $storeId,
        #[ORM\Column(type: 'guid')]
        private string $productId,
        #[ORM\Column(type: 'guid')]
        private string $stockId,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $stockMovementId,
        #[ORM\Column(length: 32)]
        private string $type,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $quantity,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $unitCost,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 6)]
        private string $value,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 6)]
        private string $previousTotalValue,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 6)]
        private string $resultingTotalValue,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $previousAverageCost,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $resultingAverageCost,
        #[ORM\Column(length: 3)]
        private string $currency,
        #[ORM\Column(length: 64)]
        private string $sourceType,
        #[ORM\Column(length: 128, nullable: true)]
        private ?string $sourceReferenceId,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $occurredAt,
        #[ORM\Column(type: 'guid')]
        private string $correlationId,
    ) {}

    public static function fromDomain(StockValuationMovement $movement): self
    {
        return new self(
            $movement->id()->toString(),
            $movement->stockValuationId()->toString(),
            $movement->organizationId()->toString(),
            $movement->storeId()->toString(),
            $movement->productId()->toString(),
            $movement->stockId()->toString(),
            $movement->stockMovementId()?->toString(),
            $movement->type()->value,
            $movement->quantity()->toString(),
            $movement->unitCost()->amount()->toString(),
            $movement->value()->amount()->toString(),
            $movement->previousTotalValue()->amount()->toString(),
            $movement->resultingTotalValue()->amount()->toString(),
            $movement->previousAverageCost()->amount()->toString(),
            $movement->resultingAverageCost()->amount()->toString(),
            $movement->value()->currency()->code(),
            $movement->source()->type(),
            $movement->source()->referenceId(),
            $movement->occurredAt(),
            $movement->correlationId()->toString(),
        );
    }

    public function id(): string
    {
        return $this->id;
    }
    public function stockValuationId(): string
    {
        return $this->stockValuationId;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function storeId(): string
    {
        return $this->storeId;
    }
    public function productId(): string
    {
        return $this->productId;
    }
    public function stockId(): string
    {
        return $this->stockId;
    }
    public function stockMovementId(): ?string
    {
        return $this->stockMovementId;
    }
    public function type(): string
    {
        return $this->type;
    }
    public function quantity(): string
    {
        return $this->quantity;
    }
    public function unitCost(): string
    {
        return $this->unitCost;
    }
    public function value(): string
    {
        return $this->value;
    }
    public function previousTotalValue(): string
    {
        return $this->previousTotalValue;
    }
    public function resultingTotalValue(): string
    {
        return $this->resultingTotalValue;
    }
    public function previousAverageCost(): string
    {
        return $this->previousAverageCost;
    }
    public function resultingAverageCost(): string
    {
        return $this->resultingAverageCost;
    }
    public function currency(): string
    {
        return $this->currency;
    }
    public function sourceType(): string
    {
        return $this->sourceType;
    }
    public function sourceReferenceId(): ?string
    {
        return $this->sourceReferenceId;
    }
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
    public function correlationId(): string
    {
        return $this->correlationId;
    }
}
