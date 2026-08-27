<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class MoneyInput
{
    public function __construct(public string $amount, public string $currency) {}
}
