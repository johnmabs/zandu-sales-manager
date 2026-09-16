<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

final readonly class GoodsReceiptView
{
    /** @param list<array<string, string|null>> $lines */
    public function __construct(
        public string $id,
        public string $storeId,
        public string $supplierId,
        public ?string $purchaseOrderId,
        public string $number,
        public string $status,
        public ?string $supplierDeliveryNote,
        public ?string $notes,
        public array $lines,
        public string $createdAt,
        public ?string $postedAt,
        public ?string $cancelledAt,
        public int $version,
    ) {}
}
