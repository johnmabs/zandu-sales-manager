<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Sales\Domain\{Sale,SaleRepository};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class AddSaleLineHandler
{
    public function __construct(private SaleRepository $sales, private SaleLineFactory $lines, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private SaleEventPublisher $events) {}

    public function __invoke(AddSaleLine $command): Sale
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): Sale {
            $sale = $this->sales->getForUpdate($command->actor->organizationId(), $command->saleId);
            $this->authorization->authorize($command->actor, PermissionCode::SaleUpdateDraft, ResourceScope::store($sale->organizationId(), $sale->storeId()));
            $this->guard->assertStore($command->actor, $sale->storeId());
            $sale->addLine($this->lines->create($sale, $command->productId, $command->productPackagingId, $command->quantity));
            $this->sales->save($sale);
            $this->events->publish('sale_updated', $sale, $command->actor, ['change' => 'LINE_ADDED']);

            return $sale;
        });
    }
}
