<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Infrastructure\Persistence\Orm;

use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\InventoryCosting\Domain\Valuation\StockValuation;

#[ORM\Entity]
#[ORM\Table(name: 'stock_valuation', schema: 'inventory_costing')]
#[ORM\UniqueConstraint(name: 'stock_valuation_stock_unique', columns: ['organization_id', 'stock_id'])]
final class StockValuationRecord
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
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $quantityOnHand,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 6)]
        private string $totalValue,
        #[ORM\Column(length: 3)]
        private string $currency,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(StockValuation $valuation): self
    {
        return new self(
            $valuation->id()->toString(),
            $valuation->organizationId()->toString(),
            $valuation->storeId()->toString(),
            $valuation->productId()->toString(),
            $valuation->stockId()->toString(),
            $valuation->quantityOnHand()->toString(),
            $valuation->totalValue()->amount()->toString(),
            $valuation->currency()->code(),
            $valuation->version(),
        );
    }

    public function synchronize(StockValuation $valuation): void
    {
        $this->quantityOnHand = $valuation->quantityOnHand()->toString();
        $this->totalValue = $valuation->totalValue()->amount()->toString();
    }

    public function id(): string
    {
        return $this->id;
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

    public function quantityOnHand(): string
    {
        return $this->quantityOnHand;
    }

    public function totalValue(): string
    {
        return $this->totalValue;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function version(): int
    {
        return $this->version;
    }
}
