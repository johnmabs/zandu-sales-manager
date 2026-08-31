<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\StockTransferDraft;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockTransferId;

final readonly class CancelStockTransfer
{
    public function __construct(public StockTransferId $transferId, public string $reason, public ActorContext $actorContext) {}
}
