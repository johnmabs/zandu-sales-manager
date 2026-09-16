<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

final readonly class CashRegisterUpdateInput
{
    public function __construct(public string $code, public string $name, #[\ApiPlatform\Metadata\ApiProperty(required: true, description: 'Version read by the client before editing.')] #[\Symfony\Component\Validator\Constraints\Positive] public int $expectedVersion) {}
}
