<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockTransferReceiveInput
{
    /** @param list<array{lineId:string,receivedQuantity:string}> $lines */
    public function __construct(public array $lines) {}
}
