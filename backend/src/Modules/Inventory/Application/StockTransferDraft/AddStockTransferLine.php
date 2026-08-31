<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\StockTransferDraft;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId, StockTransferId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class AddStockTransferLine
{
    public function __construct(public StockTransferId $transferId, public ProductId $productId, public Quantity $requestedQuantity, public ActorContext $actorContext) {}
}
