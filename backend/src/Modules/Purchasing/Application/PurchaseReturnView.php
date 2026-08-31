<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

final readonly class PurchaseReturnView
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
