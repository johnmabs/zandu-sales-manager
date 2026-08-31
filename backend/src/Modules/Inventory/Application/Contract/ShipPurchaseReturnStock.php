<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ShipPurchaseReturnStock
{
    /** @param non-empty-list<array{productId: ProductId, baseQuantity: Quantity}> $items */
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public PurchaseReturnId $purchaseReturnId,
        public array $items,
        public string $reason,
        public DateTimeImmutable $occurredAt,
        public ActorContext $actorContext,
    ) {}
}
