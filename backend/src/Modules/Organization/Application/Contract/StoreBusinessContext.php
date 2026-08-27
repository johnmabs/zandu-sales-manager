<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

final readonly class StoreBusinessContext
{
    public function __construct(public string $timeZone, public string $currency) {}
}
