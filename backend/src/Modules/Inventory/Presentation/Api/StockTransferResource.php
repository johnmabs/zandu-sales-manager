<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Delete, Get, GetCollection, Link, Patch, Post};

#[ApiResource(operations: [
    new GetCollection(name: 'stock_transfer_list', uriTemplate: '/stock-transfers', provider: StockTransferProvider::class),
    new Post(name: 'stock_transfer_create', uriTemplate: '/stock-transfers', read: false, input: StockTransferCreateInput::class, processor: StockTransferProcessor::class),
    new Get(name: 'stock_transfer_get', uriTemplate: '/stock-transfers/{id}', provider: StockTransferProvider::class),
    new Post(name: 'stock_transfer_line_add', uriTemplate: '/stock-transfers/{id}/lines', read: false, input: StockTransferLineInput::class, processor: StockTransferProcessor::class),
    new Patch(name: 'stock_transfer_line_update', uriTemplate: '/stock-transfers/{id}/lines/{lineId}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id']), 'lineId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, input: StockTransferLineUpdateInput::class, processor: StockTransferProcessor::class),
    new Delete(name: 'stock_transfer_line_remove', uriTemplate: '/stock-transfers/{id}/lines/{lineId}', uriVariables: ['id' => new Link(fromClass: self::class, identifiers: ['id']), 'lineId' => new Link(fromClass: self::class, identifiers: ['id'])], read: false, processor: StockTransferProcessor::class),
    new Post(name: 'stock_transfer_ship', uriTemplate: '/stock-transfers/{id}/ship', read: false, input: StockTransferShipInput::class, processor: StockTransferProcessor::class),
    new Post(name: 'stock_transfer_receive', uriTemplate: '/stock-transfers/{id}/receive', read: false, input: StockTransferReceiveInput::class, processor: StockTransferProcessor::class),
    new Post(name: 'stock_transfer_cancel', uriTemplate: '/stock-transfers/{id}/cancel', read: false, input: StockTransferCancelInput::class, processor: StockTransferProcessor::class),
])]
final readonly class StockTransferResource
{
    /** @param list<array{id:string,productId:string,requestedQuantity:string,shippedQuantity:?string,receivedQuantity:?string,transitDiscrepancy:?string}> $lines */
    public function __construct(
        public string $id,
        public string $sourceStoreId,
        public string $destinationStoreId,
        public string $status,
        public array $lines,
        public bool $hasTransitDiscrepancy,
        public string $createdAt,
        public ?string $shippedAt,
        public ?string $receivedAt,
        public ?string $cancellationReason,
        public ?string $cancelledAt,
        public int $version,
    ) {}
}
