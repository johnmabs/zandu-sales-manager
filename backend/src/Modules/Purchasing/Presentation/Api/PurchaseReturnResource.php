<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new Post(name: 'purchase_return_create', uriTemplate: '/stores/{storeId}/purchase-returns', read: false, input: PurchaseReturnCreateInput::class, processor: PurchaseReturnProcessor::class),
    new Post(name: 'purchase_return_add_line', uriTemplate: '/purchase-returns/{id}/lines', read: false, input: PurchaseReturnLineInput::class, processor: PurchaseReturnProcessor::class),
    new Post(name: 'purchase_return_ship', uriTemplate: '/purchase-returns/{id}/ship', read: false, input: false, processor: PurchaseReturnProcessor::class),
    new Post(name: 'purchase_return_cancel', uriTemplate: '/purchase-returns/{id}/cancel', read: false, input: false, processor: PurchaseReturnProcessor::class),
    new Get(name: 'purchase_return_get', uriTemplate: '/purchase-returns/{id}', provider: PurchaseReturnProvider::class),
])]
final readonly class PurchaseReturnResource
{
    /** @param list<array{id: string, productId: string, baseQuantity: string, goodsReceiptLineId: ?string}> $lines */
    public function __construct(
        public string $id,
        public string $sourceStoreId,
        public string $supplierId,
        public ?string $goodsReceiptId,
        public ?string $purchaseOrderId,
        public string $status,
        public string $reason,
        public array $lines,
        public string $createdAt,
        public ?string $shippedAt,
        public ?string $cancelledAt,
        public int $version,
    ) {}
}
