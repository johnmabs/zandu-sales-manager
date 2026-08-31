<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Application\Contract\{StockTransferIdempotency, StockTransferPhase};
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\SharedKernel\Identity\StockTransferId;

final class InMemoryStockTransferIdempotency implements StockTransferIdempotency
{
    /** @var array<string, string> */
    private array $claims = [];

    public function claim(StockTransferId $transferId, StockTransferPhase $phase, string $commandId, string $payloadHash): bool
    {
        $identity = implode(':', [$transferId->toString(), $phase->value, $commandId]);
        if (isset($this->claims[$identity])) {
            if (!hash_equals($this->claims[$identity], $payloadHash)) {
                throw InventoryRuleViolation::with('IDEMPOTENCY_CONFLICT', 'Idempotency key was already used with a different stock transfer payload.');
            }

            return false;
        }
        $this->claims[$identity] = $payloadHash;

        return true;
    }
}
