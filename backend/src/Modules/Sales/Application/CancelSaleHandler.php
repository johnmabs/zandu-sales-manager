<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Sales\Domain\{Sale,SaleRepository};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CancelSaleHandler
{
    public function __construct(private SaleRepository $sales, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private SaleEventPublisher $events) {}

    public function __invoke(CancelSale $command): Sale
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): Sale {
            $sale = $this->sales->getForUpdate($command->actor->organizationId(), $command->saleId);
            $this->authorization->authorize($command->actor, PermissionCode::SaleCancelDraft, ResourceScope::store($sale->organizationId(), $sale->storeId()));
            $this->guard->assertStore($command->actor, $sale->storeId());
            $sale->cancel($command->actor, $this->clock->now());
            $this->sales->save($sale);
            $this->events->publish('sale_cancelled', $sale, $command->actor);

            return $sale;
        });
    }
}
