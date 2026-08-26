<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class AdjustStockInput
{
    public function __construct(public string $delta, public string $reason) {}
}
