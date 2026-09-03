<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockCountCreateInput
{
    /** @param list<string> $productIds */
    public function __construct(
        public string $scopeType,
        public array $productIds = [],
        public string $mode = 'BLIND',
    ) {}
}
