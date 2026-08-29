<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\PostGoodsReceipt;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptId;

final readonly class PostGoodsReceipt
{
    public function __construct(
        public GoodsReceiptId $goodsReceiptId,
        public ActorContext $actorContext,
        public string $commandId = '',
    ) {}
}
