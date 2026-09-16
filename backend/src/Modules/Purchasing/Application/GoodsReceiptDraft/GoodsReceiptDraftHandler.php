<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\GoodsReceiptDraft;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode, StoreBusinessContextProvider};
use Zandu\Modules\Purchasing\Application\GoodsReceiptLineFactory;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\{GoodsReceipt, GoodsReceiptRepository};
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\{PurchaseOrderLine, PurchaseOrderRepository};
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{GoodsReceiptId, GoodsReceiptLineId, ProductId, ProductPackagingId, PurchaseOrderLineId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class GoodsReceiptDraftHandler
{
    public function __construct(private GoodsReceiptRepository $receipts, private PurchaseOrderRepository $orders, private GoodsReceiptLineFactory $lines, private StoreBusinessContextProvider $stores, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private Clock $clock) {}

    public function add(AddGoodsReceiptLine $command): GoodsReceipt
    {
        return $this->change($command->receiptId, $command->actor, function (GoodsReceipt $receipt) use ($command): void {
            $receipt->addLine($this->line($receipt, $command->productId, $command->packagingId, $command->purchaseOrderLineId, $command->quantity, $command->unitCost));
        });
    }

    public function update(UpdateGoodsReceiptLine $command): GoodsReceipt
    {
        return $this->change($command->receiptId, $command->actor, function (GoodsReceipt $receipt) use ($command): void {
            $receipt->updateLine($this->line($receipt, $command->productId, $command->packagingId, $command->purchaseOrderLineId, $command->quantity, $command->unitCost, $command->lineId));
        });
    }

    public function remove(RemoveGoodsReceiptLine $command): GoodsReceipt
    {
        return $this->change($command->receiptId, $command->actor, static fn(GoodsReceipt $receipt) => $receipt->removeLine($command->lineId));
    }

    public function cancel(CancelGoodsReceipt $command): GoodsReceipt
    {
        $organizationId = $command->actor->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): GoodsReceipt {
            $receipt = $this->receipts->getForUpdate($organizationId, $command->receiptId);
            $this->authorization->authorize($command->actor, PermissionCode::GoodsReceiptCancel, ResourceScope::store($organizationId, $receipt->storeId()));
            $this->guard->assertStore($command->actor, $receipt->storeId(), OperationalMode::Remediation);
            $receipt->cancel($command->actor->actorId(), $this->clock->now());
            $this->receipts->save($receipt);

            return $receipt;
        });
    }

    /** @param callable(GoodsReceipt): void $mutation */
    private function change(GoodsReceiptId $receiptId, ActorContext $actor, callable $mutation): GoodsReceipt
    {
        $organizationId = $actor->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($receiptId, $actor, $organizationId, $mutation): GoodsReceipt {
            $receipt = $this->receipts->getForUpdate($organizationId, $receiptId);
            $this->authorization->authorize($actor, PermissionCode::GoodsReceiptCreate, ResourceScope::store($organizationId, $receipt->storeId()));
            $this->guard->assertStore($actor, $receipt->storeId());
            $mutation($receipt);
            $this->receipts->save($receipt);

            return $receipt;
        });
    }

    private function line(GoodsReceipt $receipt, ?ProductId $productId, ?ProductPackagingId $packagingId, ?PurchaseOrderLineId $purchaseOrderLineId, Quantity $quantity, Money $unitCost, ?GoodsReceiptLineId $lineId = null): \Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine
    {
        if (null === $receipt->purchaseOrderId()) {
            if (null === $productId || null !== $purchaseOrderLineId) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_PURCHASE_ORDER_LINE_INVALID', 'Direct receipt lines require productId and forbid purchaseOrderLineId.');
            }
            $currency = $this->stores->provide($receipt->organizationId(), $receipt->storeId())->currency;
            if ($unitCost->currency()->code() !== $currency) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Receipt currency must match destination store currency.');
            }

            return $this->lines->createDirect($receipt->organizationId(), $receipt->id(), $productId, $packagingId, $quantity, $unitCost, $lineId);
        }

        if (null === $purchaseOrderLineId || null !== $productId || null !== $packagingId) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_PURCHASE_ORDER_LINE_INVALID', 'Linked receipt lines require only purchaseOrderLineId.');
        }
        $order = $this->orders->get($receipt->organizationId(), $receipt->purchaseOrderId());
        $orderLine = array_find($order->lines(), static fn(PurchaseOrderLine $candidate): bool => $candidate->id()->equals($purchaseOrderLineId));
        if (!$orderLine instanceof PurchaseOrderLine) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_PRODUCT_NOT_ORDERED', 'Every linked receipt line must belong to the purchase order.');
        }
        if (!$unitCost->currency()->equals($order->currency())) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Actual receipt cost must use the purchase order currency.');
        }

        return $this->lines->createLinked($receipt->id(), $orderLine, $quantity, $unitCost, $lineId);
    }
}
