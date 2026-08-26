<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

final readonly class ProductPriceCreateInput
{
    public function __construct(public string $priceListId, public string $productId, public string $packagingId, public string $amount, public string $currency, public ?string $validFrom, public ?string $validTo) {}
}
