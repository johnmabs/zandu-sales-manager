<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class ReturnSaleCreateInput
{
    public function __construct(public ?string $reason = null) {}
}
