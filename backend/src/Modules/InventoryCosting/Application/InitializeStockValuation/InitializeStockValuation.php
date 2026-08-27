<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application\InitializeStockValuation;

use InvalidArgumentException;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\{ProductId, StoreId};

final readonly class InitializeStockValuation
{
    public string $reason;

    public function __construct(
        public StoreId $storeId,
        public ProductId $productId,
        public Decimal $openingUnitCost,
        string $reason,
        public ActorContext $actorContext,
    ) {
        $reason = trim($reason);
        if ('' === $reason || strlen($reason) > 255) {
            throw new InvalidArgumentException('Valuation initialization reason must contain between 1 and 255 characters.');
        }
        $this->reason = $reason;
    }
}
