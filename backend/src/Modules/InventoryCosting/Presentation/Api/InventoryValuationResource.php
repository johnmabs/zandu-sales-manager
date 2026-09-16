<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Get, GetCollection, Link, Post};

#[ApiResource(operations: [
    new GetCollection(
        name: 'inventory_valuation_list',
        uriTemplate: '/stores/{storeId}/inventory-valuations',
        uriVariables: ['storeId' => new Link(fromClass: self::class, identifiers: ['id'])],
        provider: InventoryValuationProvider::class,
    ),
    new Get(
        name: 'inventory_valuation_get',
        uriTemplate: '/stores/{storeId}/inventory-valuations/{productId}',
        uriVariables: [
            'storeId' => new Link(fromClass: self::class, identifiers: ['id']),
            'productId' => new Link(fromClass: self::class, identifiers: ['id']),
        ],
        provider: InventoryValuationProvider::class,
    ),
    new Post(
        name: 'inventory_valuation_initialize',
        uriTemplate: '/stores/{storeId}/inventory-valuations/{productId}/initialize',
        uriVariables: [
            'storeId' => new Link(fromClass: self::class, identifiers: ['id']),
            'productId' => new Link(fromClass: self::class, identifiers: ['id']),
        ],
        read: false,
        input: InitializeStockValuationInput::class,
        processor: InventoryValuationProcessor::class,
    ),
])]
final readonly class InventoryValuationResource
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $storeId,
        public string $productId,
        public string $stockId,
        public string $quantityOnHand,
        public string $totalValue,
        public string $currency,
        public string $averageUnitCost,
        public int $version,
    ) {}
}
