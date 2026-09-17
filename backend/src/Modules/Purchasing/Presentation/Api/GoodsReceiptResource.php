<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Delete, Get, GetCollection, Patch, Post};

#[ApiResource(operations: [
    new GetCollection(name: 'goods_receipt_list', uriTemplate: '/goods-receipts', provider: GoodsReceiptProvider::class, extraProperties: ['zandu_cursor_pagination' => true, 'zandu_cursor_direction' => 'desc']),
    new Post(name: 'goods_receipt_create', uriTemplate: '/stores/{storeId}/goods-receipts', read: false, input: GoodsReceiptCreateInput::class, processor: GoodsReceiptProcessor::class),
    new Get(name: 'goods_receipt_get', uriTemplate: '/goods-receipts/{id}', provider: GoodsReceiptProvider::class),
    new Post(name: 'goods_receipt_line_add', uriTemplate: '/goods-receipts/{id}/lines', read: false, input: GoodsReceiptLineInput::class, processor: GoodsReceiptProcessor::class),
    new Patch(name: 'goods_receipt_line_update', uriTemplate: '/goods-receipts/{id}/lines/{lineId}', read: false, input: GoodsReceiptLineUpdateInput::class, processor: GoodsReceiptProcessor::class),
    new Delete(name: 'goods_receipt_line_remove', uriTemplate: '/goods-receipts/{id}/lines/{lineId}', read: false, output: GoodsReceiptResource::class, processor: GoodsReceiptProcessor::class),
    new Post(name: 'goods_receipt_post', uriTemplate: '/goods-receipts/{id}/post', read: false, input: GoodsReceiptPostInput::class, processor: GoodsReceiptProcessor::class),
    new Post(name: 'goods_receipt_cancel', uriTemplate: '/goods-receipts/{id}/cancel', read: false, input: false, processor: GoodsReceiptProcessor::class),
])]
final readonly class GoodsReceiptResource
{
    /** @param list<array<string, string|null>> $lines */
    public function __construct(public string $id, public string $storeId, public string $supplierId, public ?string $purchaseOrderId, public string $number, public string $status, public ?string $supplierDeliveryNote, public ?string $notes, public array $lines, public string $createdAt, public ?string $postedAt, public ?string $cancelledAt, public int $version) {}
}
