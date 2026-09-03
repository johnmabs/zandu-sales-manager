<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

final readonly class StockCountView
{
    /**
     * @param list<string> $requestedProductIds
     * @param list<array<string, int|string|null>> $lines
     */
    public function __construct(
        public string $id,
        public string $storeId,
        public string $status,
        public string $mode,
        public string $scopeType,
        public array $requestedProductIds,
        public array $lines,
        public int $totalLineCount,
        public int $countedLineCount,
        public int $reconciledLineCount,
        public string $createdAt,
        public ?string $startedAt,
        public ?string $finalizationStartedAt,
        public ?string $completedAt,
        public ?string $cancelledAt,
        public int $version,
    ) {}
}
