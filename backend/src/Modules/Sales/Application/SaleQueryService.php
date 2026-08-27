<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Sales\Domain\{Sale,SaleRepository};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SaleId;

final readonly class SaleQueryService
{
    public function __construct(private SaleRepository $sales, private AuthorizationService $authorization) {}

    public function get(ActorContext $actor, SaleId $saleId): Sale
    {
        $sale = $this->sales->get($actor->organizationId(), $saleId);
        $this->authorization->authorize($actor, PermissionCode::SaleRead, ResourceScope::store($sale->organizationId(), $sale->storeId()));

        return $sale;
    }
}
