<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\GoodsReceiptDraft;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptId;

final readonly class CancelGoodsReceipt
{
    public function __construct(public GoodsReceiptId $receiptId, public ActorContext $actor) {}
}
