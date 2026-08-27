<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class SaleLineInput
{
    public function __construct(public string $productId, public string $productPackagingId, public string $quantity) {}
}
