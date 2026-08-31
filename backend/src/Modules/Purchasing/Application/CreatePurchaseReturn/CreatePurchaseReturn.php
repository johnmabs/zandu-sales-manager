<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreatePurchaseReturn;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class CreatePurchaseReturn
{
    /** @param list<PurchaseReturnInput> $lines */
    public function __construct(
        public StoreId $sourceStoreId,
        public GoodsReceiptId $goodsReceiptId,
        public string $reason,
        public array $lines,
        public ActorContext $actorContext,
    ) {}
}
