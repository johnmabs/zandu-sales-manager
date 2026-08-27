<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard,StoreBusinessContextProvider};
use Zandu\Modules\Sales\Domain\{Sale,SaleRepository};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{IdGenerator,SaleId};
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateSaleHandler
{
    public function __construct(private SaleRepository $sales, private StoreBusinessContextProvider $stores, private DecimalFactory $decimals, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private SaleEventPublisher $events) {}

    public function __invoke(CreateSale $command): Sale
    {
        $organizationId = $command->actor->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Sale {
            $this->authorization->authorize($command->actor, PermissionCode::SaleCreate, ResourceScope::store($organizationId, $command->storeId));
            $this->guard->assertStore($command->actor, $command->storeId);
            $currency = Currency::fromCode($this->stores->provide($organizationId, $command->storeId)->currency);
            $sale = Sale::create(SaleId::generate($this->ids), $organizationId, $command->storeId, $currency->code(), new Money($this->decimals->fromString('0'), $currency), $command->actor, $this->clock->now());
            $this->sales->save($sale);
            $this->events->publish('sale_created', $sale, $command->actor);

            return $sale;
        });
    }
}
