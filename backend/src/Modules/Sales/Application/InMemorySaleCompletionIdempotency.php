<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Application\Contract\SaleCompletionIdempotency;
use Zandu\Modules\Sales\Domain\SalesRuleViolation;
use Zandu\SharedKernel\Identity\SaleId;

final class InMemorySaleCompletionIdempotency implements SaleCompletionIdempotency
{
    /** @var array<string,string> */
    private array $completed = [];
    public function claim(SaleId $saleId, string $key, string $payloadHash): bool
    {
        $identity = $saleId->toString() . ':' . $key;
        if (isset($this->completed[$identity])) {
            if ($this->completed[$identity] !== $payloadHash) {
                throw SalesRuleViolation::with('IDEMPOTENCY_CONFLICT', 'Idempotency key was already used with a different payload.');
            }

            return false;
        }
        $this->completed[$identity] = $payloadHash;

        return true;
    }
}
