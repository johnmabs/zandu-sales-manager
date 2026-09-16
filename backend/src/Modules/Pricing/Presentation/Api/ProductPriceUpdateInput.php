<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

final readonly class ProductPriceUpdateInput
{
    public function __construct(public string $amount, public string $currency, public ?string $validFrom, public ?string $validTo, #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')] #[\Symfony\Component\Validator\Constraints\Positive] public int $expectedVersion) {}
}
