<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(operations: [new Get(name: 'effective_product_price', uriTemplate: '/products/{productId}/packagings/{packagingId}/effective-price', provider: EffectiveProductPriceProvider::class)])]
final readonly class EffectiveProductPriceResource
{
    public function __construct(public string $priceListId, public string $productPriceId, public string $amount, public string $currency, public int $sourceVersion) {}
}
