<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application\Contract;

use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Money\Currency;

final readonly class PricingSnapshot
{
    public function __construct(
        private ProductId $productId,
        private ProductPackagingId $packagingId,
        private Decimal $packagingFactor,
        private PriceListId $priceListId,
        private ProductPriceId $productPriceId,
        private Decimal $priceAmount,
        private Currency $currency,
        private int $packagingSourceVersion,
        private int $priceListSourceVersion,
        private int $productPriceSourceVersion,
    ) {}

    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function packagingId(): ProductPackagingId
    {
        return $this->packagingId;
    }
    public function packagingFactor(): Decimal
    {
        return $this->packagingFactor;
    }
    public function priceListId(): PriceListId
    {
        return $this->priceListId;
    }
    public function productPriceId(): ProductPriceId
    {
        return $this->productPriceId;
    }
    public function priceAmount(): Decimal
    {
        return $this->priceAmount;
    }
    public function currency(): Currency
    {
        return $this->currency;
    }
    public function packagingSourceVersion(): int
    {
        return $this->packagingSourceVersion;
    }
    public function priceListSourceVersion(): int
    {
        return $this->priceListSourceVersion;
    }
    public function productPriceSourceVersion(): int
    {
        return $this->productPriceSourceVersion;
    }
}
