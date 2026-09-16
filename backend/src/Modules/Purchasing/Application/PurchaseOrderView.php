<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

final readonly class PurchaseOrderView
{
    /** @param list<array<string, string|null>> $lines */
    public function __construct(
        public string $id,
        public string $destinationStoreId,
        public string $supplierId,
        public string $number,
        public string $status,
        public string $currency,
        public string $expectedTotal,
        public array $lines,
        public string $createdAt,
        public ?string $confirmedAt,
        public ?string $closedAt,
        public ?string $closedReason,
        public ?string $cancelledAt,
        public int $version,
    ) {}
}
