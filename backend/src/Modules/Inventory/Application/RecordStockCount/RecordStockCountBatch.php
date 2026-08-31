<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\RecordStockCount;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockCountId;

final readonly class RecordStockCountBatch
{
    /** @var non-empty-list<StockCountEntry> */
    public array $entries;

    /** @param list<StockCountEntry> $entries */
    public function __construct(public StockCountId $stockCountId, array $entries, public ActorContext $actorContext)
    {
        if ([] === $entries) {
            throw new \InvalidArgumentException('A stock count batch must contain at least one entry.');
        }
        $this->entries = $entries;
    }
}
