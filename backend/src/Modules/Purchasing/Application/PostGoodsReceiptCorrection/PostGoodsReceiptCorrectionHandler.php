<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\PostGoodsReceiptCorrection;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{ApplyGoodsReceiptCorrection, InventoryGoodsReceiptCorrector};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\{GoodsReceipt, GoodsReceiptLine, GoodsReceiptRepository, GoodsReceiptStatus};
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\{GoodsReceiptCorrection, GoodsReceiptCorrectionRepository, GoodsReceiptCorrectionStatus};
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\{PurchaseOrder, PurchaseOrderRepository};
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class PostGoodsReceiptCorrectionHandler
{
    public function __construct(private GoodsReceiptCorrectionRepository $corrections, private GoodsReceiptRepository $receipts, private PurchaseOrderRepository $orders, private InventoryGoodsReceiptCorrector $inventory, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private SecurityAuditTrail $audit, private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock) {}

    public function __invoke(PostGoodsReceiptCorrection $command): GoodsReceiptCorrection
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): GoodsReceiptCorrection {
            $correction = $this->corrections->getForUpdate($organizationId, $command->correctionId);
            $receipt = $this->receipts->getForUpdate($organizationId, $correction->goodsReceiptId());
            $scope = ResourceScope::store($organizationId, $receipt->storeId());
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchasingReceiptCorrect, $scope);
            if (GoodsReceiptCorrectionStatus::Posted === $correction->status()) {
                return $correction;
            }
            if (GoodsReceiptStatus::Posted !== $receipt->status()) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_RECEIPT_NOT_POSTED', 'Only a posted goods receipt can be corrected.');
            }
            $this->guard->assertStore($command->actorContext, $receipt->storeId());
            $posted = $this->corrections->postedDifferenceByProduct($organizationId, $receipt->id());
            $items = [];
            foreach ($correction->lines() as $line) {
                $original = array_find($receipt->lines(), static fn(GoodsReceiptLine $candidate): bool => $candidate->productId()->equals($line->productId()));
                if (!$original instanceof GoodsReceiptLine) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_PRODUCT_NOT_RECEIVED', 'Correction product no longer belongs to the receipt.');
                }
                $current = $original->receivedBaseQuantity();
                if (isset($posted[$line->productId()->toString()])) {
                    $current = $current->add($posted[$line->productId()->toString()]);
                }
                if (!$current->equals($line->currentEffectiveQuantity())) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_STALE', 'Current effective receipt quantity changed before correction posting.');
                }
                $items[] = ['productId' => $line->productId(), 'difference' => $line->difference(), 'incomingUnitCost' => $original->inventoryUnitCost()];
            }
            $now = $this->clock->now();
            $applied = $this->inventory->apply(new ApplyGoodsReceiptCorrection($organizationId, $receipt->storeId(), $correction->id(), $items, $correction->reason(), $now, $command->actorContext));
            $expected = count(array_filter($correction->lines(), static fn($line): bool => !$line->difference()->isZero()));
            if ($applied !== $expected) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CORRECTION_CONFLICT', 'Correction stock effects are incomplete.');
            }
            $order = $this->linkedOrder($receipt);
            if (null !== $order) {
                foreach ($correction->lines() as $line) {
                    if ($line->difference()->isZero()) {
                        continue;
                    }
                    $receiptLine = array_find($receipt->lines(), static fn(GoodsReceiptLine $candidate): bool => $candidate->productId()->equals($line->productId()));
                    $order->correctReceivedQuantity($receiptLine?->purchaseOrderLineId() ?? throw new \LogicException('Linked receipt line is required.'), $line->difference(), $command->actorContext->actorId(), $now);
                    $this->orders->save($order);
                }
            }
            $correction->post($command->actorContext->actorId(), $now);
            $this->corrections->save($correction);
            $this->audit->recordSuccess($command->actorContext, SecurityAction::GoodsReceiptCorrected, ResourceReference::for('goods_receipt_correction', $correction->id()), SafeAuditMetadata::fromArray(['reason' => $correction->reason(), 'lineCount' => count($correction->lines()), 'commandId' => $command->commandId]), $now);
            $this->outbox->append(new OutboxMessage(OutboxMessageId::generate($this->ids), $organizationId, 'purchasing.goods_receipt_corrected.v1', ['goodsReceiptCorrectionId' => $correction->id()->toString(), 'goodsReceiptId' => $receipt->id()->toString(), 'lineCount' => count($correction->lines())], $command->actorContext->correlationId(), $command->actorContext->causationId(), $now));
            return $correction;
        });
    }

    private function linkedOrder(GoodsReceipt $receipt): ?PurchaseOrder
    {
        return null === $receipt->purchaseOrderId() ? null : $this->orders->getForUpdate($receipt->organizationId(), $receipt->purchaseOrderId());
    }
}
