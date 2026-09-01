<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockTransferShipInput
{
    /** @param list<array{lineId:string,shippedQuantity:string}> $lines */
    public function __construct(public array $lines) {}
}
