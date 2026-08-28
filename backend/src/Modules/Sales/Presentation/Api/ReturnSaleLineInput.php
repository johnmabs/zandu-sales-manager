<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class ReturnSaleLineInput
{
    public function __construct(public string $saleLineId, public string $quantity, public bool $restock, public ?string $reason = null) {}
}
