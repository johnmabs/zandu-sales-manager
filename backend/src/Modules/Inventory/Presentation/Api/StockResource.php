<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'stock_list', uriTemplate: '/stores/{storeId}/stocks', provider: StockProvider::class),
    new Get(name: 'stock_get', uriTemplate: '/stores/{storeId}/stocks/{productId}', provider: StockProvider::class),
    new Post(name: 'stock_initialize', uriTemplate: '/stores/{storeId}/stocks/{productId}/initialize', input: InitializeStockInput::class, processor: StockProcessor::class),
    new Post(name: 'stock_adjust', uriTemplate: '/stores/{storeId}/stocks/{productId}/adjust', input: AdjustStockInput::class, processor: StockProcessor::class),
])]
final readonly class StockResource
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $storeId,
        public string $productId,
        public string $quantityOnHand,
        public bool $initialized,
        public int $version,
    ) {}
}
