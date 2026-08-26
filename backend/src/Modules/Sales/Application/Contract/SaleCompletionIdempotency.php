<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Identity\SaleId;

interface SaleCompletionIdempotency
{
    public function wasCompleted(SaleId $saleId, string $key): bool;
    public function markCompleted(SaleId $saleId, string $key): void;
}
