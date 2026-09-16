<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class SupplierInput
{
    public function __construct(public string $name, public ?string $phone = null, public ?string $email = null, public ?string $address = null, public ?string $notes = null) {}
}
