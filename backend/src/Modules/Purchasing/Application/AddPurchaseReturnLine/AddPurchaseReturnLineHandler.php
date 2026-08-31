<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\AddPurchaseReturnLine;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptStatus;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturn;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnLine;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\PurchaseReturnLineId;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class AddPurchaseReturnLineHandler
{
    public function __construct(
        private PurchaseReturnRepository $purchaseReturns,
        private GoodsReceiptRepository $goodsReceipts,
        private GoodsReceiptCorrectionRepository $corrections,
        private IdGenerator $ids,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(AddPurchaseReturnLine $command): PurchaseReturn
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseReturn {
            $return = $this->purchaseReturns->getForUpdate($organizationId, $command->purchaseReturnId);
            $scope = ResourceScope::store($organizationId, $return->sourceStoreId());
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseReturnCreate, $scope);
            $this->operationalGuard->assertStore($command->actorContext, $return->sourceStoreId());

            $receiptId = $return->goodsReceiptId();
            if (null === $receiptId) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_SOURCE_INVALID', 'A return line requires a source goods receipt.');
            }

            $receipt = $this->goodsReceipts->getForUpdate($organizationId, $receiptId);
            if (GoodsReceiptStatus::Posted !== $receipt->status()) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_RECEIPT_NOT_POSTED', 'Only a posted goods receipt can be returned.');
            }
            $receiptLine = array_find($receipt->lines(), static fn(GoodsReceiptLine $candidate): bool => $candidate->id()->equals($command->goodsReceiptLineId));
            if (!$receiptLine instanceof GoodsReceiptLine) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_LINE_NOT_RECEIVED', 'Every purchase return line must belong to its source goods receipt.');
            }

            $correctedQuantities = $this->corrections->postedDifferenceByProduct($organizationId, $receipt->id());
            $shippedQuantities = $this->purchaseReturns->shippedQuantityByProduct($organizationId, $receipt->id());
            $productKey = $receiptLine->productId()->toString();
            $returnable = $receiptLine->receivedBaseQuantity()
                ->add($correctedQuantities[$productKey] ?? $this->zero($receiptLine->receivedBaseQuantity()))
                ->subtract($shippedQuantities[$productKey] ?? $this->zero($receiptLine->receivedBaseQuantity()));
            if ($command->baseQuantity->compareTo($returnable) > 0) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_EXCEEDS_RETURNABLE', 'Purchase return quantity exceeds the receipt returnable balance.');
            }

            $return->addLine(new PurchaseReturnLine(PurchaseReturnLineId::generate($this->ids), $return->id(), $receiptLine->productId(), $command->baseQuantity, $receiptLine->id()));
            $this->purchaseReturns->save($return);

            return $return;
        });
    }

    private function zero(Quantity $quantity): Quantity
    {
        return $quantity->subtract($quantity);
    }
}
