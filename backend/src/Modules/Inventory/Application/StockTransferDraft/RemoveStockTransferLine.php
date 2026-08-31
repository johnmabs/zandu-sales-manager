<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\StockTransferDraft;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{StockTransferId, StockTransferLineId};

final readonly class RemoveStockTransferLine
{
    public function __construct(public StockTransferId $transferId, public StockTransferLineId $lineId, public ActorContext $actorContext) {}
}
