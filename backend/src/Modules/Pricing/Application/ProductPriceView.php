<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

final readonly class ProductPriceView
{
    public function __construct(public string $id, public string $organizationId, public string $priceListId, public string $productId, public string $packagingId, public string $amount, public string $currency, public string $status, public ?string $validFrom, public ?string $validTo, public string $createdAt, public int $version) {}
}
