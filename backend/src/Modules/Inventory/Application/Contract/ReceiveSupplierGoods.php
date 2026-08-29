<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{GoodsReceiptId, OrganizationId, ProductId, StoreId, SupplierId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ReceiveSupplierGoods
{
    /** @param non-empty-list<array{productId: ProductId, baseQuantity: Quantity, inventoryUnitCost: Money}> $items */
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public GoodsReceiptId $goodsReceiptId,
        public SupplierId $supplierId,
        public array $items,
        public DateTimeImmutable $receivedAt,
        public ActorContext $actorContext,
    ) {}
}
