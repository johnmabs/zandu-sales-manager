<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection;

use RuntimeException;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;

final class GoodsReceiptCorrectionNotFound extends RuntimeException
{
    public static function withId(GoodsReceiptCorrectionId $id): self
    {
        return new self(sprintf('Goods receipt correction "%s" was not found.', $id->toString()));
    }
}
