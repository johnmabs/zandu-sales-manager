<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class GoodsReceiptLineInput
{
    public function __construct(public string $enteredReceivedQuantity, public string $unitCost, public string $currency, public ?string $productId = null, public ?string $productPackagingId = null, public ?string $purchaseOrderLineId = null) {}
}
