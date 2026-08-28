<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

final readonly class ReturnSaleView
{
    /** @param list<array<string, bool|string|array<string,string>|null>> $lines */
    public function __construct(
        public string $id,
        public string $saleId,
        public string $storeId,
        public string $status,
        public ?string $reason,
        public array $lines,
        public ?string $businessDate,
        public ?string $completedAt,
        public ?string $cancelledAt,
        public int $version,
    ) {}
}
