<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection;

use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class GoodsReceiptCorrectionLine
{
    public function __construct(
        private GoodsReceiptCorrectionId $correctionId,
        private ProductId $productId,
        private Quantity $originalReceivedQuantity,
        private Quantity $currentEffectiveQuantity,
        private Quantity $correctedReceivedQuantity,
    ) {
        if ($originalReceivedQuantity->isZero() || $originalReceivedQuantity->isNegative()) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_ORIGINAL_QUANTITY_INVALID', 'Original received quantity must be positive.');
        }
        if ($currentEffectiveQuantity->isNegative() || $correctedReceivedQuantity->isNegative()) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_QUANTITY_INVALID', 'Effective and corrected quantities cannot be negative.');
        }
    }

    public function difference(): Quantity
    {
        return $this->correctedReceivedQuantity->subtract($this->currentEffectiveQuantity);
    }

    public function correctionId(): GoodsReceiptCorrectionId
    {
        return $this->correctionId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function originalReceivedQuantity(): Quantity
    {
        return $this->originalReceivedQuantity;
    }
    public function currentEffectiveQuantity(): Quantity
    {
        return $this->currentEffectiveQuantity;
    }
    public function correctedReceivedQuantity(): Quantity
    {
        return $this->correctedReceivedQuantity;
    }
}
