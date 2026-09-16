<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\GoodsReceiptDraft;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{GoodsReceiptId, GoodsReceiptLineId};

final readonly class RemoveGoodsReceiptLine
{
    public function __construct(public GoodsReceiptId $receiptId, public GoodsReceiptLineId $lineId, public ActorContext $actor) {}
}
