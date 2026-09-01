<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\SharedKernel\Identity\{OrganizationId, StoreId};

final readonly class PurchasingStoreClosureBlockerProvider implements StoreClosureBlockerProvider
{
    public function __construct(
        private PurchaseOrderRepository $purchaseOrders,
        private GoodsReceiptRepository $goodsReceipts,
        private PurchaseReturnRepository $purchaseReturns,
        private GoodsReceiptCorrectionRepository $receiptCorrections,
    ) {}

    public function blockers(OrganizationId $organizationId, StoreId $storeId): array
    {
        $blockers = [];
        if ($this->purchaseOrders->hasOpenForStore($organizationId, $storeId)) {
            $blockers[] = 'OPEN_PURCHASE_ORDER';
        }
        if ($this->goodsReceipts->hasDraftForStore($organizationId, $storeId)) {
            $blockers[] = 'DRAFT_GOODS_RECEIPT';
        }
        if ($this->purchaseReturns->hasOpenForStore($organizationId, $storeId)) {
            $blockers[] = 'OPEN_PURCHASE_RETURN';
        }
        if ($this->receiptCorrections->hasOpenForStore($organizationId, $storeId)) {
            $blockers[] = 'OPEN_GOODS_RECEIPT_CORRECTION';
        }

        return $blockers;
    }
}
