<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseReturn;

use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Identity\{GoodsReceiptLineId, ProductId, PurchaseReturnId, PurchaseReturnLineId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class PurchaseReturnLine
{
    public function __construct(private PurchaseReturnLineId $id, private PurchaseReturnId $purchaseReturnId, private ProductId $productId, private Quantity $baseQuantity, private ?GoodsReceiptLineId $goodsReceiptLineId)
    {
        if ($baseQuantity->isZero() || $baseQuantity->isNegative()) {
            throw PurchasingRuleViolation::with('PURCHASE_RETURN_QUANTITY_INVALID', 'Purchase return quantity must be positive.');
        }
    }
    public function id(): PurchaseReturnLineId
    {
        return $this->id;
    }
    public function purchaseReturnId(): PurchaseReturnId
    {
        return $this->purchaseReturnId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function baseQuantity(): Quantity
    {
        return $this->baseQuantity;
    }
    public function goodsReceiptLineId(): ?GoodsReceiptLineId
    {
        return $this->goodsReceiptLineId;
    }
}
