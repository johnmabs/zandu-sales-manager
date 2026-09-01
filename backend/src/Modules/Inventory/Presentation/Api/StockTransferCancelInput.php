<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockTransferCancelInput
{
    public function __construct(public string $reason) {}
}
