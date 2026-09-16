<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class GoodsReceiptPostInput
{
    public function __construct(public ?string $overReceiptReason = null) {}
}
