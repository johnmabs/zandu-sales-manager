<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class GoodsReceiptCreateInput
{
    /** @param list<array<string, string|null>> $lines */
    public function __construct(public string $number, public array $lines, public ?string $supplierId = null, public ?string $purchaseOrderId = null, public ?string $supplierDeliveryNote = null, public ?string $notes = null) {}
}
