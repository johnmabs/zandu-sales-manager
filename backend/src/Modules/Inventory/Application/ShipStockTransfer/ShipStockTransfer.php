<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ShipStockTransfer;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockTransferId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ShipStockTransfer
{ /** @param array<string,Quantity> $shippedQuantities */ public function __construct(public StockTransferId $transferId, public array $shippedQuantities, public ActorContext $actorContext, public string $commandId = '') {}
}
