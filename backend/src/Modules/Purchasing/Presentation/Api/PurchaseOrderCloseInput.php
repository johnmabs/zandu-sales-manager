<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final readonly class PurchaseOrderCloseInput
{
    public function __construct(public ?string $reason = null) {}
}
