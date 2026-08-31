<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{OrganizationId, StockTransferId, StoreId};

final readonly class ShipStockTransferStock
{ /** @param list<array{productId: \Zandu\SharedKernel\Identity\ProductId,baseQuantity: \Zandu\SharedKernel\Quantity\Quantity}> $items */ public function __construct(public OrganizationId $organizationId, public StoreId $sourceStoreId, public StockTransferId $transferId, public array $items, public ActorContext $actorContext, public DateTimeImmutable $occurredAt) {}
}
