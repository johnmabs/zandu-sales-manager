<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(operations: [new GetCollection(name: 'product_price_list', uriTemplate: '/product-prices', provider: ProductPriceProvider::class),new Get(name: 'product_price_get', uriTemplate: '/product-prices/{id}', provider: ProductPriceProvider::class)])]
final readonly class ProductPriceResource
{
    public function __construct(public string $id, public string $organizationId, public string $priceListId, public string $productId, public string $packagingId, public string $amount, public string $currency, public string $status, public ?string $validFrom, public ?string $validTo, public string $createdAt, public int $version) {}
}
