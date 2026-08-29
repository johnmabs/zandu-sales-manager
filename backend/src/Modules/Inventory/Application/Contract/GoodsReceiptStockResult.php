<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Identity\GoodsReceiptId;

final readonly class GoodsReceiptStockResult
{
    public const int CONTRACT_VERSION = 1;

    /** @param list<ReceivedGoodsItem> $items */
    public function __construct(
        public GoodsReceiptId $goodsReceiptId,
        public bool $alreadyReceived,
        public array $items = [],
    ) {}
}
