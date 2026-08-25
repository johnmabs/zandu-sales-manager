<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application\Contract;

use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Money\Currency;

final readonly class ResolvedProductPrice
{
    public function __construct(
        private PriceListId $priceListId,
        private ProductPriceId $productPriceId,
        private Decimal $amount,
        private Currency $currency,
        private int $sourceVersion,
    ) {}

    public function priceListId(): PriceListId
    {
        return $this->priceListId;
    }
    public function productPriceId(): ProductPriceId
    {
        return $this->productPriceId;
    }
    public function amount(): Decimal
    {
        return $this->amount;
    }
    public function currency(): Currency
    {
        return $this->currency;
    }
    public function sourceVersion(): int
    {
        return $this->sourceVersion;
    }
}
