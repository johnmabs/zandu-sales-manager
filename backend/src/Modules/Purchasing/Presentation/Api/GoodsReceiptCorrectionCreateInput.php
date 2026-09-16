<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class GoodsReceiptCorrectionCreateInput
{
    /** @param list<array{productId: string, correctedReceivedQuantity: string}> $lines */
    public function __construct(public string $reason, public array $lines) {}
}
