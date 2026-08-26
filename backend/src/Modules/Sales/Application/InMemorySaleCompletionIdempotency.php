<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Application\Contract\SaleCompletionIdempotency;
use Zandu\SharedKernel\Identity\SaleId;

final class InMemorySaleCompletionIdempotency implements SaleCompletionIdempotency
{
    /** @var array<string,true> */
    private array $completed = [];
    public function wasCompleted(SaleId $saleId, string $key): bool
    {
        return isset($this->completed[$saleId->toString() . ':' . $key]);
    }
    public function markCompleted(SaleId $saleId, string $key): void
    {
        $this->completed[$saleId->toString() . ':' . $key] = true;
    }
}
