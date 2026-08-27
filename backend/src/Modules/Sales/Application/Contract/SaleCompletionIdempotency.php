<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Identity\SaleId;

interface SaleCompletionIdempotency
{
    /** Returns false when the same request was already claimed. */
    public function claim(SaleId $saleId, string $key, string $payloadHash): bool;
}
