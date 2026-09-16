<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\{ApiResource, Get, Post};

#[ApiResource(operations: [
    new Post(name: 'goods_receipt_correction_create', uriTemplate: '/goods-receipts/{id}/corrections', read: false, input: GoodsReceiptCorrectionCreateInput::class, processor: GoodsReceiptCorrectionProcessor::class),
    new Get(name: 'goods_receipt_correction_get', uriTemplate: '/goods-receipt-corrections/{id}', provider: GoodsReceiptCorrectionProvider::class),
    new Post(name: 'goods_receipt_correction_post', uriTemplate: '/goods-receipt-corrections/{id}/post', read: false, input: false, processor: GoodsReceiptCorrectionProcessor::class),
])]
final readonly class GoodsReceiptCorrectionResource
{
    /** @param list<array<string, string>> $lines */
    public function __construct(public string $id, public string $goodsReceiptId, public string $reason, public string $status, public array $lines, public string $createdAt, public ?string $postedAt, public int $version) {}
}
