<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ApplyGoodsReceiptCorrection
{
    /** @param list<array{productId: ProductId, difference: Quantity, incomingUnitCost: Money}> $items */
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public GoodsReceiptCorrectionId $correctionId,
        public array $items,
        public string $reason,
        public DateTimeImmutable $occurredAt,
        public ActorContext $actorContext,
    ) {}
}
