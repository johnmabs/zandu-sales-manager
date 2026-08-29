<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateGoodsReceiptCorrection;

use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class GoodsReceiptCorrectionInput
{
    public function __construct(public ProductId $productId, public Quantity $correctedReceivedQuantity) {}
}
