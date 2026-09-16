<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, GetCollection, Link};

#[ApiResource(operations: [
    new GetCollection(
        name: 'inventory_valuation_movement_list',
        uriTemplate: '/stores/{storeId}/inventory-valuations/{productId}/movements',
        uriVariables: [
            'storeId' => new Link(fromClass: InventoryValuationResource::class, identifiers: ['id']),
            'productId' => new Link(fromClass: InventoryValuationResource::class, identifiers: ['id']),
        ],
        provider: InventoryValuationMovementProvider::class,
    ),
])]
final readonly class InventoryValuationMovementResource
{
    public function __construct(
        public string $id,
        public string $stockValuationId,
        public string $organizationId,
        public string $storeId,
        public string $productId,
        public string $stockId,
        public ?string $stockMovementId,
        public string $type,
        public string $quantity,
        public string $unitCost,
        public string $value,
        public string $previousTotalValue,
        public string $resultingTotalValue,
        public string $previousAverageCost,
        public string $resultingAverageCost,
        public string $currency,
        public string $sourceType,
        public ?string $sourceReferenceId,
        public string $occurredAt,
        public string $correlationId,
    ) {}
}
