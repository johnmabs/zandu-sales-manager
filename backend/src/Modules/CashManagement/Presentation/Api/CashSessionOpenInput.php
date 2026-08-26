<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

final readonly class CashSessionOpenInput
{
    public function __construct(public string $amount, public string $currency) {}
}
