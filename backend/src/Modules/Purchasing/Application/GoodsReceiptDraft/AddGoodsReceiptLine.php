<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\GoodsReceiptDraft;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{GoodsReceiptId, ProductId, ProductPackagingId, PurchaseOrderLineId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class AddGoodsReceiptLine
{
    public function __construct(public GoodsReceiptId $receiptId, public ?ProductId $productId, public ?ProductPackagingId $packagingId, public ?PurchaseOrderLineId $purchaseOrderLineId, public Quantity $quantity, public Money $unitCost, public ActorContext $actor) {}
}
