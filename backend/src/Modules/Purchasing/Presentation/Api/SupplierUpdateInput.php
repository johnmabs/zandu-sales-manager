<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class SupplierUpdateInput
{
    public function __construct(public string $name, public ?string $phone, public ?string $email, public ?string $address, public ?string $notes, #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')] #[\Symfony\Component\Validator\Constraints\Positive] public int $expectedVersion) {}
}
