<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ReceiveStockTransfer;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockTransferId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ReceiveStockTransfer
{ /** @param array<string,Quantity> $receivedQuantities */ public function __construct(public StockTransferId $transferId, public array $receivedQuantities, public ActorContext $actorContext, public string $commandId = '') {}
}
