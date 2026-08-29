<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateDirectGoodsReceipt;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContextProvider;
use Zandu\Modules\Purchasing\Application\GoodsReceiptLineFactory;
use Zandu\Modules\Purchasing\Application\PurchasingPolicy;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptRepository;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateDirectGoodsReceiptHandler
{
    public function __construct(
        private GoodsReceiptRepository $goodsReceipts,
        private TenantSupplierLoader $suppliers,
        private StoreBusinessContextProvider $stores,
        private GoodsReceiptLineFactory $lineFactory,
        private PurchasingPolicy $policy,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreateDirectGoodsReceipt $command): GoodsReceipt
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): GoodsReceipt {
            $scope = ResourceScope::store($organizationId, $command->storeId);
            $this->authorization->authorize($command->actorContext, PermissionCode::GoodsReceiptCreate, $scope);
            $this->operationalGuard->assertStore($command->actorContext, $command->storeId);
            if (!$this->policy->allowsDirectReceipt()) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_PURCHASE_ORDER_REQUIRED', 'A purchase order is required to receive goods.');
            }
            if ([] === $command->lines) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_EMPTY', 'A direct goods receipt must contain at least one line.');
            }
            $this->suppliers->get($command->supplierId, $command->actorContext)->ensureUsable();
            $storeCurrency = $this->stores->provide($organizationId, $command->storeId)->currency;
            $receipt = GoodsReceipt::create(
                GoodsReceiptId::generate($this->idGenerator),
                $organizationId,
                $command->storeId,
                $command->supplierId,
                null,
                GoodsReceiptNumber::fromString($command->number),
                $command->supplierDeliveryNote,
                $command->notes,
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            foreach ($command->lines as $line) {
                if ($line->inventoryUnitCost->currency()->code() !== $storeCurrency) {
                    throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Receipt currency must match destination store currency.');
                }
                $receipt->addLine($this->lineFactory->createDirect(
                    $organizationId,
                    $receipt->id(),
                    $line->productId,
                    $line->packagingId,
                    $line->enteredReceivedQuantity,
                    $line->inventoryUnitCost,
                ));
            }
            $this->goodsReceipts->save($receipt);

            return $receipt;
        });
    }
}
