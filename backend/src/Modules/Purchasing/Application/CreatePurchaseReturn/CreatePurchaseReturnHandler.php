<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreatePurchaseReturn;

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
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Identity\PurchaseReturnLineId;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreatePurchaseReturnHandler
{
    public function __construct(
        private GoodsReceiptRepository $goodsReceipts,
        private GoodsReceiptCorrectionRepository $corrections,
        private PurchaseReturnRepository $purchaseReturns,
        private IdGenerator $ids,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreatePurchaseReturn $command): PurchaseReturn
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseReturn {
            $receipt = $this->goodsReceipts->getForUpdate($organizationId, $command->goodsReceiptId);
            $scope = ResourceScope::store($organizationId, $receipt->storeId());
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseReturnCreate, $scope);
            $this->operationalGuard->assertStore($command->actorContext, $receipt->storeId());

            if (!$receipt->storeId()->equals($command->sourceStoreId)) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_STORE_MISMATCH', 'Purchase return store must match its source goods receipt.');
            }
            if (GoodsReceiptStatus::Posted !== $receipt->status()) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_RECEIPT_NOT_POSTED', 'Only a posted goods receipt can be returned.');
            }
            if ([] === $command->lines) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_EMPTY', 'A purchase return must contain at least one line.');
            }

            $correctedQuantities = $this->corrections->postedDifferenceByProduct($organizationId, $receipt->id());
            $shippedQuantities = $this->purchaseReturns->shippedQuantityByProduct($organizationId, $receipt->id());
            $return = PurchaseReturn::create(
                PurchaseReturnId::generate($this->ids),
                $organizationId,
                $receipt->storeId(),
                $receipt->supplierId(),
                $receipt->id(),
                $receipt->purchaseOrderId(),
                $command->reason,
                $command->actorContext->actorId(),
                $this->clock->now(),
            );

            foreach ($command->lines as $input) {
                $receiptLine = array_find(
                    $receipt->lines(),
                    static fn(GoodsReceiptLine $candidate): bool => $candidate->id()->equals($input->goodsReceiptLineId),
                );
                if (!$receiptLine instanceof GoodsReceiptLine) {
                    throw PurchasingRuleViolation::with('PURCHASE_RETURN_LINE_NOT_RECEIVED', 'Every purchase return line must belong to its source goods receipt.');
                }

                $productKey = $receiptLine->productId()->toString();
                $returnable = $receiptLine->receivedBaseQuantity()
                    ->add($correctedQuantities[$productKey] ?? $this->zero($receiptLine->receivedBaseQuantity()))
                    ->subtract($shippedQuantities[$productKey] ?? $this->zero($receiptLine->receivedBaseQuantity()));
                if ($input->baseQuantity->compareTo($returnable) > 0) {
                    throw PurchasingRuleViolation::with('PURCHASE_RETURN_EXCEEDS_RETURNABLE', 'Purchase return quantity exceeds the receipt returnable balance.');
                }

                $return->addLine(new PurchaseReturnLine(
                    PurchaseReturnLineId::generate($this->ids),
                    $return->id(),
                    $receiptLine->productId(),
                    $input->baseQuantity,
                    $receiptLine->id(),
                ));
            }

            $this->purchaseReturns->save($return);

            return $return;
        });
    }

    private function zero(Quantity $quantity): Quantity
    {
        return $quantity->subtract($quantity);
    }
}
