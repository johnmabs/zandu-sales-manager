<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class UpdateSaleLineInput
{
    public function __construct(public string $quantity, #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')] #[\Symfony\Component\Validator\Constraints\Positive] public int $expectedVersion) {}
}
