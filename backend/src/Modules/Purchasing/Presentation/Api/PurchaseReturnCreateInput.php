<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class PurchaseReturnCreateInput
{
    /** @param list<array<string, mixed>> $lines */
    public function __construct(
        public string $goodsReceiptId = '',
        public string $reason = '',
        public array $lines = [],
    ) {}
}
