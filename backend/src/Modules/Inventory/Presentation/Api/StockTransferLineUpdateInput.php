<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockTransferLineUpdateInput
{
    public function __construct(public string $requestedQuantity, #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')] #[\Symfony\Component\Validator\Constraints\Positive] public int $expectedVersion) {}
}
