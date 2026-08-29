<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateDirectGoodsReceipt;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class CreateDirectGoodsReceipt
{
    /** @param list<DirectGoodsReceiptLine> $lines */
    public function __construct(
        public StoreId $storeId,
        public SupplierId $supplierId,
        public string $number,
        public ?string $supplierDeliveryNote,
        public ?string $notes,
        public array $lines,
        public ActorContext $actorContext,
    ) {}
}
