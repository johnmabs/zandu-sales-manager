<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

final readonly class CashSessionCloseInput
{
    public function __construct(public string $amount, public string $currency) {}
}
