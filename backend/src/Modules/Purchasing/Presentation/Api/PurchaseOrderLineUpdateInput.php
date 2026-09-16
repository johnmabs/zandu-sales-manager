<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class PurchaseOrderLineUpdateInput
{
    public function __construct(public string $productId, public string $enteredQuantity, public string $unitCost, public string $currency, public ?string $productPackagingId, #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')] #[\Symfony\Component\Validator\Constraints\Positive] public int $expectedVersion) {}
}
