<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockMovement;

use Zandu\SharedKernel\Identity\{GoodsReceiptCorrectionId, GoodsReceiptId, PurchaseReturnId, ReturnSaleId, SaleId};
use Zandu\SharedKernel\Identity\Uuid;

final readonly class StockMovementSource
{
    private function __construct(private string $type, private ?string $referenceId) {}
    public static function initialization(): self
    {
        return new self('INITIALIZATION', null);
    }
    public static function manualAdjustment(?Uuid $referenceId = null): self
    {
        return new self('MANUAL_ADJUSTMENT', $referenceId?->toString());
    }
    public static function sale(SaleId $saleId): self
    {
        return new self('SALE', $saleId->toString());
    }
    public static function saleReturn(ReturnSaleId $returnSaleId): self
    {
        return new self('RETURN', $returnSaleId->toString());
    }
    public static function goodsReceipt(GoodsReceiptId $goodsReceiptId): self
    {
        return new self('GOODS_RECEIPT', $goodsReceiptId->toString());
    }
    public static function goodsReceiptCorrection(GoodsReceiptCorrectionId $correctionId): self
    {
        return new self('GOODS_RECEIPT_CORRECTION', $correctionId->toString());
    }
    public static function purchaseReturn(PurchaseReturnId $purchaseReturnId): self
    {
        return new self('PURCHASE_RETURN', $purchaseReturnId->toString());
    }
    public function type(): string
    {
        return $this->type;
    }
    public function referenceId(): ?string
    {
        return $this->referenceId;
    }
}
