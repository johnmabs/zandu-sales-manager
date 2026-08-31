<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Identity\StockTransferId;

interface StockTransferIdempotency
{
    /** Returns false when the exact same command was already claimed. */
    public function claim(StockTransferId $transferId, StockTransferPhase $phase, string $commandId, string $payloadHash): bool;
}
