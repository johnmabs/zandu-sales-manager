<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class PurchaseOrderLineInput
{
    public function __construct(public string $productId, public string $enteredQuantity, public string $unitCost, public string $currency, public ?string $productPackagingId = null) {}
}
