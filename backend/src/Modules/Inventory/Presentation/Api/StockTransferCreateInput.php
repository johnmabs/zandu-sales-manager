<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockTransferCreateInput
{
    public function __construct(public string $sourceStoreId, public string $destinationStoreId) {}
}
