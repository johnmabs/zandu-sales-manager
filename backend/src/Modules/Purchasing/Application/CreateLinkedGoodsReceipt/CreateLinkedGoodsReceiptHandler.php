<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateLinkedGoodsReceipt;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContextProvider;
use Zandu\Modules\Purchasing\Application\GoodsReceiptLineFactory;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderStatus;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateLinkedGoodsReceiptHandler
{
    public function __construct(
        private GoodsReceiptRepository $goodsReceipts,
        private PurchaseOrderRepository $purchaseOrders,
        private TenantSupplierLoader $suppliers,
        private StoreBusinessContextProvider $stores,
        private GoodsReceiptLineFactory $lineFactory,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreateLinkedGoodsReceipt $command): GoodsReceipt
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): GoodsReceipt {
            $order = $this->purchaseOrders->getForUpdate($organizationId, $command->purchaseOrderId);
            if (null !== $command->expectedStoreId && !$order->destinationStoreId()->equals($command->expectedStoreId)) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_STORE_MISMATCH', 'Receipt store must match its purchase order.');
            }
            if (null !== $command->expectedSupplierId && !$order->supplierId()->equals($command->expectedSupplierId)) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_SUPPLIER_MISMATCH', 'Receipt supplier must match its purchase order.');
            }
            $scope = ResourceScope::store($organizationId, $order->destinationStoreId());
            $this->authorization->authorize($command->actorContext, PermissionCode::GoodsReceiptCreate, $scope);
            $this->operationalGuard->assertStore($command->actorContext, $order->destinationStoreId());
            if (!in_array($order->status(), [PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::PartiallyReceived], true)) {
                throw PurchasingRuleViolation::with('PURCHASE_ORDER_NOT_RECEIVABLE', 'Only a confirmed or partially received purchase order can receive stock.');
            }
            if ([] === $command->lines) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_EMPTY', 'A linked goods receipt must contain at least one line.');
            }
            $this->suppliers->get($order->supplierId(), $command->actorContext)->ensureUsable();
            $storeCurrency = $this->stores->provide($organizationId, $order->destinationStoreId())->currency;
            if ($order->currency()->code() !== $storeCurrency) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Purchase order currency must match destination store currency.');
            }

            $receipt = GoodsReceipt::create(
                GoodsReceiptId::generate($this->idGenerator),
                $organizationId,
                $order->destinationStoreId(),
                $order->supplierId(),
                $order->id(),
                GoodsReceiptNumber::fromString($command->number),
                $command->supplierDeliveryNote,
                $command->notes,
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            foreach ($command->lines as $line) {
                $orderLine = array_find(
                    $order->lines(),
                    static fn(PurchaseOrderLine $candidate): bool => $candidate->id()->equals($line->purchaseOrderLineId),
                );
                if (!$orderLine instanceof PurchaseOrderLine) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_PRODUCT_NOT_ORDERED', 'Every linked receipt line must belong to the purchase order.');
                }
                if (null !== $line->actualUnitCost && !$line->actualUnitCost->currency()->equals($order->currency())) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Actual receipt cost must use the purchase order currency.');
                }
                $receiptLine = $this->lineFactory->createLinked(
                    $receipt->id(),
                    $orderLine,
                    $line->enteredReceivedQuantity,
                    $line->actualUnitCost,
                );
                $receipt->addLine($receiptLine);
            }
            $this->goodsReceipts->save($receipt);

            return $receipt;
        });
    }
}
