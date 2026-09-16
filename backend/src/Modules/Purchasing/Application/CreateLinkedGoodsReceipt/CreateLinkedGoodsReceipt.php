<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateLinkedGoodsReceipt;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class CreateLinkedGoodsReceipt
{
    /** @param list<LinkedGoodsReceiptLine> $lines */
    public function __construct(
        public PurchaseOrderId $purchaseOrderId,
        public string $number,
        public ?string $supplierDeliveryNote,
        public ?string $notes,
        public array $lines,
        public ActorContext $actorContext,
        public ?StoreId $expectedStoreId = null,
        public ?SupplierId $expectedSupplierId = null,
    ) {}
}
