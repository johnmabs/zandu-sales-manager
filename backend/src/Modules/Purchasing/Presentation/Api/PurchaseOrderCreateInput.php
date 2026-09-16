<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class PurchaseOrderCreateInput
{
    public function __construct(public string $supplierId, public string $number, public string $currency) {}
}
