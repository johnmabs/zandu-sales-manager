<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

final readonly class PriceListUpdateInput
{
    public function __construct(public string $code, public string $name, public ?string $validFrom, public ?string $validTo, public int $priority) {}
}
