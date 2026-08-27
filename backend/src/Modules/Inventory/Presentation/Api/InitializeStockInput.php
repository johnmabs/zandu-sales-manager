<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class InitializeStockInput
{
    public function __construct(public string $quantity, public string $unitCost) {}
}
