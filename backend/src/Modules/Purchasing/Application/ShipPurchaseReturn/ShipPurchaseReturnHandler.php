<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ShipPurchaseReturn;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\InventoryPurchaseReturnShipper;
use Zandu\Modules\Inventory\Application\Contract\PurchaseReturnStockUnavailable;
use Zandu\Modules\Inventory\Application\Contract\ShipPurchaseReturnStock;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptStatus;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturn;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnStatus;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OutboxMessageId;
use Zandu\SharedKernel\Messaging\OutboxMessage;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ShipPurchaseReturnHandler
{
    public function __construct(
        private PurchaseReturnRepository $purchaseReturns,
        private GoodsReceiptRepository $goodsReceipts,
        private GoodsReceiptCorrectionRepository $corrections,
        private InventoryPurchaseReturnShipper $inventory,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
        private OutboxRepository $outbox,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function __invoke(ShipPurchaseReturn $command): PurchaseReturn
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseReturn {
            $return = $this->purchaseReturns->getForUpdate($organizationId, $command->purchaseReturnId);
            $scope = ResourceScope::store($organizationId, $return->sourceStoreId());
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseReturnShip, $scope);
            if (PurchaseReturnStatus::Shipped === $return->status()) {
                return $return;
            }
            if (PurchaseReturnStatus::Draft !== $return->status()) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_NOT_EDITABLE', 'Only a draft purchase return can be shipped.');
            }
            if ([] === $return->lines()) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_EMPTY', 'A purchase return must contain at least one line.');
            }
            $this->operationalGuard->assertStore($command->actorContext, $return->sourceStoreId());

            $receiptId = $return->goodsReceiptId() ?? throw PurchasingRuleViolation::with(
                'PURCHASE_RETURN_SOURCE_INVALID',
                'A purchase return must be linked to one source goods receipt.',
            );
            $receipt = $this->goodsReceipts->getForUpdate($organizationId, $receiptId);
            if (GoodsReceiptStatus::Posted !== $receipt->status()) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_RECEIPT_NOT_POSTED', 'Only a posted goods receipt can be returned.');
            }
            if (!$receipt->storeId()->equals($return->sourceStoreId()) || !$receipt->supplierId()->equals($return->supplierId())) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_SOURCE_INVALID', 'Purchase return store and supplier must match the source receipt.');
            }

            $correctedQuantities = $this->corrections->postedDifferenceByProduct($organizationId, $receipt->id());
            $shippedQuantities = $this->purchaseReturns->shippedQuantityByProduct($organizationId, $receipt->id());
            $items = [];
            foreach ($return->lines() as $line) {
                $goodsReceiptLineId = $line->goodsReceiptLineId();
                if (null === $goodsReceiptLineId) {
                    throw PurchasingRuleViolation::with('PURCHASE_RETURN_LINE_NOT_RECEIVED', 'Every purchase return line must be linked to its source receipt.');
                }
                $receiptLine = array_find(
                    $receipt->lines(),
                    static fn(GoodsReceiptLine $candidate): bool => $candidate->id()->equals($goodsReceiptLineId)
                        && $candidate->productId()->equals($line->productId()),
                );
                if (!$receiptLine instanceof GoodsReceiptLine) {
                    throw PurchasingRuleViolation::with('PURCHASE_RETURN_LINE_NOT_RECEIVED', 'Every purchase return line must still belong to its source receipt.');
                }

                $productKey = $line->productId()->toString();
                $returnable = $receiptLine->receivedBaseQuantity();
                if (isset($correctedQuantities[$productKey])) {
                    $returnable = $returnable->add($correctedQuantities[$productKey]);
                }
                if (isset($shippedQuantities[$productKey])) {
                    $returnable = $returnable->subtract($shippedQuantities[$productKey]);
                }
                if ($line->baseQuantity()->compareTo($returnable) > 0) {
                    throw PurchasingRuleViolation::with('PURCHASE_RETURN_EXCEEDS_RETURNABLE', 'Purchase return quantity exceeds the current receipt returnable balance.');
                }
                $items[] = ['productId' => $line->productId(), 'baseQuantity' => $line->baseQuantity()];
            }

            $now = $this->clock->now();
            try {
                $shipped = $this->inventory->ship(new ShipPurchaseReturnStock(
                    $organizationId,
                    $return->sourceStoreId(),
                    $return->id(),
                    $items,
                    $return->reason(),
                    $now,
                    $command->actorContext,
                ));
            } catch (PurchaseReturnStockUnavailable) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_INSUFFICIENT_STOCK', 'Purchase return quantity exceeds the currently available stock.');
            }
            if ($shipped !== count($return->lines())) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_CONFLICT', 'Purchase return stock effects are incomplete.');
            }

            $return->ship($command->actorContext->actorId(), $now);
            $this->purchaseReturns->save($return);
            $this->audit->recordSuccess(
                $command->actorContext,
                SecurityAction::PurchaseReturnShipped,
                ResourceReference::for('purchase_return', $return->id()),
                SafeAuditMetadata::fromArray([
                    'reason' => $return->reason(),
                    'lineCount' => count($return->lines()),
                    'commandId' => $command->commandId,
                ]),
                $now,
            );
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $organizationId,
                'purchasing.purchase_return_shipped.v1',
                [
                    'purchaseReturnId' => $return->id()->toString(),
                    'goodsReceiptId' => $receipt->id()->toString(),
                    'lineCount' => count($return->lines()),
                ],
                $command->actorContext->correlationId(),
                $command->actorContext->causationId(),
                $now,
            ));

            return $return;
        });
    }
}
