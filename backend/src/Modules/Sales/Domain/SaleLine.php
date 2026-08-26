<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use Zandu\SharedKernel\Identity\{ProductId,ProductPackagingId,SaleId,SaleLineId,UnitOfMeasureId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class SaleLine
{
    public function __construct(
        private SaleLineId $id,
        private SaleId $saleId,
        private ProductId $productId,
        private ProductPackagingId $productPackagingId,
        private ?string $productCodeSnapshot,
        private ?string $productNameSnapshot,
        private string $packagingCodeSnapshot,
        private ?string $packagingNameSnapshot,
        private UnitOfMeasureId $unitIdSnapshot,
        private Quantity $enteredQuantity,
        private Quantity $conversionFactorSnapshot,
        private Quantity $baseQuantity,
        private Money $unitPrice,
        private ?string $priceListId,
        private ?string $productPriceId,
        private Money $discountAmount,
        private Money $taxableAmount,
        private Money $taxAmount,
        private Money $subtotal,
        private Money $total,
        /** @var array<string,int|string> */
        private array $sourceVersions = [],
    ) {}

    public function id(): SaleLineId
    {
        return $this->id;
    }
    public function saleId(): SaleId
    {
        return $this->saleId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function productPackagingId(): ProductPackagingId
    {
        return $this->productPackagingId;
    }
    public function productCodeSnapshot(): ?string
    {
        return $this->productCodeSnapshot;
    }
    public function productNameSnapshot(): ?string
    {
        return $this->productNameSnapshot;
    }
    public function packagingCodeSnapshot(): string
    {
        return $this->packagingCodeSnapshot;
    }
    public function packagingNameSnapshot(): ?string
    {
        return $this->packagingNameSnapshot;
    }
    public function unitIdSnapshot(): UnitOfMeasureId
    {
        return $this->unitIdSnapshot;
    }
    public function enteredQuantity(): Quantity
    {
        return $this->enteredQuantity;
    }
    public function conversionFactorSnapshot(): Quantity
    {
        return $this->conversionFactorSnapshot;
    }
    public function baseQuantity(): Quantity
    {
        return $this->baseQuantity;
    }
    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }
    public function priceListId(): ?string
    {
        return $this->priceListId;
    }
    public function productPriceId(): ?string
    {
        return $this->productPriceId;
    }
    public function discountAmount(): Money
    {
        return $this->discountAmount;
    }
    public function taxableAmount(): Money
    {
        return $this->taxableAmount;
    }
    public function taxAmount(): Money
    {
        return $this->taxAmount;
    }
    public function subtotal(): Money
    {
        return $this->subtotal;
    }
    public function total(): Money
    {
        return $this->total;
    }
    /** @return array<string,int|string> */ public function sourceVersions(): array
    {
        return $this->sourceVersions;
    }
}
