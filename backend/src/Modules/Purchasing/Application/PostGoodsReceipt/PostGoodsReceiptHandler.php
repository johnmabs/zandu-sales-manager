<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\PostGoodsReceipt;

use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshotProvider;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{InventoryGoodsReceiver, ReceiveSupplierGoods};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, StoreBusinessContextProvider};
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\{GoodsReceipt, GoodsReceiptLine, GoodsReceiptRepository, GoodsReceiptStatus};
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\{PurchaseOrder, PurchaseOrderLine, PurchaseOrderRepository};
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class PostGoodsReceiptHandler
{
    public function __construct(
        private GoodsReceiptRepository $goodsReceipts,
        private PurchaseOrderRepository $purchaseOrders,
        private TenantSupplierLoader $suppliers,
        private StoreBusinessContextProvider $stores,
        private PurchasableProductSnapshotProvider $catalog,
        private InventoryGoodsReceiver $inventory,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
        private OutboxRepository $outbox,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function __invoke(PostGoodsReceipt $command): GoodsReceipt
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): GoodsReceipt {
            $receipt = $this->goodsReceipts->getForUpdate($organizationId, $command->goodsReceiptId);
            $scope = ResourceScope::store($organizationId, $receipt->storeId());
            $this->authorization->authorize($command->actorContext, PermissionCode::GoodsReceiptPost, $scope);
            if (GoodsReceiptStatus::Posted === $receipt->status()) {
                return $receipt;
            }
            $now = $this->clock->now();
            if (GoodsReceiptStatus::Draft !== $receipt->status()) {
                $receipt->post($command->actorContext->actorId(), $now);
            }
            if ([] === $receipt->lines()) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_EMPTY', 'A goods receipt must contain at least one line before posting.');
            }
            $this->operationalGuard->assertStore($command->actorContext, $receipt->storeId());
            $this->suppliers->get($receipt->supplierId(), $command->actorContext)->ensureUsable();
            $storeCurrency = $this->stores->provide($organizationId, $receipt->storeId())->currency;
            foreach ($receipt->lines() as $line) {
                $snapshot = $this->catalog->provide($organizationId, $line->productId(), $line->productPackagingId());
                if (!$line->conversionFactorSnapshot()->value()->equals($snapshot->conversionFactor)) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_SNAPSHOT_STALE', 'Goods receipt conversion snapshot no longer matches Catalog.');
                }
                if ($line->inventoryUnitCost()->currency()->code() !== $storeCurrency) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Receipt currency must match destination store currency.');
                }
            }
            $order = $this->linkedOrder($receipt);
            if (null !== $order) {
                $this->validateLinkedLines($receipt, $order);
            }
            $stock = $this->inventory->receive(new ReceiveSupplierGoods(
                $organizationId,
                $receipt->storeId(),
                $receipt->id(),
                $receipt->supplierId(),
                array_map(static fn(GoodsReceiptLine $line): array => [
                    'productId' => $line->productId(),
                    'baseQuantity' => $line->receivedBaseQuantity(),
                    'inventoryUnitCost' => $line->inventoryUnitCost(),
                ], $receipt->lines()),
                $now,
                $command->actorContext,
            ));
            if ($stock->alreadyReceived || count($stock->items) !== count($receipt->lines())) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_POST_CONFLICT', 'Receipt stock was already recorded while the receipt is still a draft.');
            }
            if (null !== $order) {
                foreach ($receipt->lines() as $line) {
                    $order->recordReceipt($line->purchaseOrderLineId() ?? throw new \LogicException('Linked receipt line is required.'), $line->receivedBaseQuantity(), $command->actorContext->actorId(), $now);
                    $this->purchaseOrders->save($order);
                }
            }
            $receipt->post($command->actorContext->actorId(), $now);
            $this->goodsReceipts->save($receipt);
            $this->audit->recordSuccess($command->actorContext, SecurityAction::GoodsReceiptPosted, ResourceReference::for('goods_receipt', $receipt->id()), SafeAuditMetadata::fromArray(['lineCount' => count($receipt->lines()), 'commandId' => $command->commandId]), $now);
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $organizationId,
                'purchasing.goods_receipt_posted.v1',
                ['goodsReceiptId' => $receipt->id()->toString(), 'storeId' => $receipt->storeId()->toString(), 'supplierId' => $receipt->supplierId()->toString(), 'lineCount' => count($receipt->lines())],
                $command->actorContext->correlationId(),
                $command->actorContext->causationId(),
                $now,
            ));

            return $receipt;
        });
    }

    private function linkedOrder(GoodsReceipt $receipt): ?PurchaseOrder
    {
        if (null === $receipt->purchaseOrderId()) {
            return null;
        }
        $order = $this->purchaseOrders->getForUpdate($receipt->organizationId(), $receipt->purchaseOrderId());
        if (!$order->supplierId()->equals($receipt->supplierId())) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_SUPPLIER_MISMATCH', 'Receipt supplier must match its purchase order.');
        }
        if (!$order->destinationStoreId()->equals($receipt->storeId())) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_STORE_MISMATCH', 'Receipt store must match its purchase order.');
        }

        return $order;
    }

    private function validateLinkedLines(GoodsReceipt $receipt, PurchaseOrder $order): void
    {
        foreach ($receipt->lines() as $receiptLine) {
            $linkedLineId = $receiptLine->purchaseOrderLineId() ?? throw new \LogicException('Linked receipt line is required.');
            $orderLine = array_find($order->lines(), static fn(PurchaseOrderLine $line): bool => $line->id()->equals($linkedLineId));
            if (!$orderLine instanceof PurchaseOrderLine || !$orderLine->productId()->equals($receiptLine->productId())) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_PRODUCT_NOT_ORDERED', 'Every linked receipt product must belong to the purchase order.');
            }
            $orderLine->withAdditionalReceipt($receiptLine->receivedBaseQuantity());
        }
    }
}
