<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'stock_list', uriTemplate: '/stores/{storeId}/stocks', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id'])], provider: StockProvider::class),
    new Get(name: 'stock_get', uriTemplate: '/stores/{storeId}/stocks/{productId}', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'productId' => new Link(fromClass: self::class, identifiers: ['id'])], provider: StockProvider::class),
    new Post(name: 'stock_initialize', uriTemplate: '/stores/{storeId}/stocks/{productId}/initialize', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'productId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: InitializeStockInput::class, processor: StockProcessor::class),
    new Post(name: 'stock_adjust', uriTemplate: '/stores/{storeId}/stocks/{productId}/adjust', uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id']),'productId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: AdjustStockInput::class, processor: StockProcessor::class),
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
