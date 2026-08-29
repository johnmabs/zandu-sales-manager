<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreatePurchaseOrder;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContextProvider;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderNumber;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreatePurchaseOrderHandler
{
    public function __construct(
        private PurchaseOrderRepository $purchaseOrders,
        private TenantSupplierLoader $suppliers,
        private StoreBusinessContextProvider $stores,
        private IdGenerator $idGenerator,
        private DecimalFactory $decimals,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CreatePurchaseOrder $command): PurchaseOrder
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseOrder {
            $scope = ResourceScope::store($organizationId, $command->destinationStoreId);
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseOrderCreate, $scope);
            $this->operationalGuard->assertStore($command->actorContext, $command->destinationStoreId);
            $this->suppliers->get($command->supplierId, $command->actorContext)->ensureUsable();
            $currency = Currency::fromCode($command->currency);
            if ($this->stores->provide($organizationId, $command->destinationStoreId)->currency !== $currency->code()) {
                throw PurchasingRuleViolation::with('PURCHASE_ORDER_CURRENCY_MISMATCH', 'Purchase order currency must match destination store currency.');
            }
            $order = PurchaseOrder::create(
                PurchaseOrderId::generate($this->idGenerator),
                $organizationId,
                $command->destinationStoreId,
                $command->supplierId,
                PurchaseOrderNumber::fromString($command->number),
                $currency,
                Money::fromString('0', $currency, $this->decimals),
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->purchaseOrders->save($order);

            return $order;
        });
    }
}
