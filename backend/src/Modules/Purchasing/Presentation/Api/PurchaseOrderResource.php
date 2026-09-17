<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Delete, Get, GetCollection, Patch, Post};

#[ApiResource(operations: [
    new GetCollection(name: 'purchase_order_list', uriTemplate: '/purchase-orders', provider: PurchaseOrderProvider::class, extraProperties: ['zandu_cursor_pagination' => true, 'zandu_cursor_direction' => 'desc']),
    new Post(name: 'purchase_order_create', uriTemplate: '/stores/{storeId}/purchase-orders', read: false, input: PurchaseOrderCreateInput::class, processor: PurchaseOrderProcessor::class),
    new Get(name: 'purchase_order_get', uriTemplate: '/purchase-orders/{id}', provider: PurchaseOrderProvider::class),
    new Post(name: 'purchase_order_line_add', uriTemplate: '/purchase-orders/{id}/lines', read: false, input: PurchaseOrderLineInput::class, processor: PurchaseOrderProcessor::class),
    new Patch(name: 'purchase_order_line_update', uriTemplate: '/purchase-orders/{id}/lines/{lineId}', read: false, input: PurchaseOrderLineUpdateInput::class, processor: PurchaseOrderProcessor::class),
    new Delete(name: 'purchase_order_line_remove', uriTemplate: '/purchase-orders/{id}/lines/{lineId}', read: false, output: PurchaseOrderResource::class, processor: PurchaseOrderProcessor::class),
    new Post(name: 'purchase_order_confirm', uriTemplate: '/purchase-orders/{id}/confirm', read: false, input: false, processor: PurchaseOrderProcessor::class),
    new Post(name: 'purchase_order_cancel', uriTemplate: '/purchase-orders/{id}/cancel', read: false, input: false, processor: PurchaseOrderProcessor::class),
    new Post(name: 'purchase_order_close', uriTemplate: '/purchase-orders/{id}/close', read: false, input: PurchaseOrderCloseInput::class, processor: PurchaseOrderProcessor::class),
])]
final readonly class PurchaseOrderResource
{
    /** @param list<array<string, string|null>> $lines */
    public function __construct(public string $id, public string $destinationStoreId, public string $supplierId, public string $number, public string $status, public string $currency, public string $expectedTotal, public array $lines, public string $createdAt, public ?string $confirmedAt, public ?string $closedAt, public ?string $closedReason, public ?string $cancelledAt, public int $version) {}
}
