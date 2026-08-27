<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

final readonly class UpdateSaleLineInput
{
    public function __construct(public string $quantity) {}
}
