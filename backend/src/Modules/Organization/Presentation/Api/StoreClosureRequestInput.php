<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

final readonly class StoreClosureRequestInput
{
    public function __construct(public string $reason) {}
}
