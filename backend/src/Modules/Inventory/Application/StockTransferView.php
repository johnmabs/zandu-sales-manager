<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

final readonly class StockTransferView
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
