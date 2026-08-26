<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

final readonly class PriceListCreateInput
{
    public function __construct(public string $code, public string $name, public string $currency, public ?string $validFrom, public ?string $validTo, public int $priority) {}
}
