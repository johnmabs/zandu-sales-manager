<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovement;

#[ORM\Entity]
#[ORM\Table(name: 'stock_movement', schema: 'inventory')]
final class StockMovementRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(type: 'guid')]
        private string $storeId,
        #[ORM\Column(type: 'guid')]
        private string $productId,
        #[ORM\Column(type: 'guid')]
        private string $stockId,
        #[ORM\Column(length: 32)]
        private string $type,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $quantity,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $previousQuantity,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $resultingQuantity,
        #[ORM\Column(length: 32)]
        private string $sourceType,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $sourceReferenceId,
        #[ORM\Column(length: 500, nullable: true)]
        private ?string $reason,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $performedBy,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $occurredAt,
    ) {}
    public static function fromAggregate(StockMovement $m): self
    {
        return new self($m->id()->toString(), $m->organizationId()->toString(), $m->storeId()->toString(), $m->productId()->toString(), $m->stockId()->toString(), $m->type()->value, $m->quantity()->toString(), $m->previousQuantity()->toString(), $m->resultingQuantity()->toString(), $m->source()->type(), $m->source()->referenceId()?->toString(), $m->reason(), $m->performedBy()?->toString(), $m->occurredAt());
    }
    public function id(): string
    {
        return $this->id;
    } public function organizationId(): string
    {
        return $this->organizationId;
    } public function storeId(): string
    {
        return $this->storeId;
    } public function productId(): string
    {
        return $this->productId;
    } public function stockId(): string
    {
        return $this->stockId;
    } public function type(): string
    {
        return $this->type;
    } public function quantity(): string
    {
        return $this->quantity;
    } public function previousQuantity(): string
    {
        return $this->previousQuantity;
    } public function resultingQuantity(): string
    {
        return $this->resultingQuantity;
    } public function sourceType(): string
    {
        return $this->sourceType;
    } public function sourceReferenceId(): ?string
    {
        return $this->sourceReferenceId;
    } public function reason(): ?string
    {
        return $this->reason;
    } public function performedBy(): ?string
    {
        return $this->performedBy;
    } public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
