<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceipt;

use RuntimeException;
use Zandu\SharedKernel\Identity\GoodsReceiptId;

final class GoodsReceiptNotFound extends RuntimeException
{
    public static function withId(GoodsReceiptId $id): self
    {
        return new self(sprintf('Goods receipt "%s" was not found.', $id->toString()));
    }
}
