<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [new GetCollection(name: 'product_price_list', uriTemplate: '/product-prices', provider: ProductPriceProvider::class), new Post(name: 'product_price_create', uriTemplate: '/product-prices', input: ProductPriceCreateInput::class, processor: ProductPriceCreateProcessor::class), new Get(name: 'product_price_get', uriTemplate: '/product-prices/{id}', provider: ProductPriceProvider::class), new Patch(name: 'product_price_update', uriTemplate: '/product-prices/{id}', read: false, input: ProductPriceUpdateInput::class, processor: ProductPriceProcessor::class), new Post(name: 'product_price_activate', uriTemplate: '/product-prices/{id}/activate', read: false, input: false, processor: ProductPriceStatusProcessor::class), new Post(name: 'product_price_deactivate', uriTemplate: '/product-prices/{id}/deactivate', read: false, input: false, processor: ProductPriceStatusProcessor::class), new Post(name: 'product_price_archive', uriTemplate: '/product-prices/{id}/archive', read: false, input: false, processor: ProductPriceStatusProcessor::class)])]
final readonly class ProductPriceResource
{
    public function __construct(public string $id, public string $organizationId, public string $priceListId, public string $productId, public string $packagingId, public string $amount, public string $currency, public string $status, public ?string $validFrom, public ?string $validTo, public string $createdAt, public int $version) {}
}
