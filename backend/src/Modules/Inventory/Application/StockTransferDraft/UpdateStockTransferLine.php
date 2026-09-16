<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\StockTransferDraft;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{StockTransferId, StockTransferLineId};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

final readonly class UpdateStockTransferLine
{
    public function __construct(public StockTransferId $transferId, public StockTransferLineId $lineId, public Quantity $requestedQuantity, public ExpectedVersion $expectedVersion, public ActorContext $actorContext) {}
}
