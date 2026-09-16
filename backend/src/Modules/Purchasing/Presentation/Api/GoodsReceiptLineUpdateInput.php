<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class GoodsReceiptLineUpdateInput
{
    public function __construct(public string $enteredReceivedQuantity, public string $unitCost, public string $currency, public ?string $productId, public ?string $productPackagingId, public ?string $purchaseOrderLineId, #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')] #[\Symfony\Component\Validator\Constraints\Positive] public int $expectedVersion) {}
}
