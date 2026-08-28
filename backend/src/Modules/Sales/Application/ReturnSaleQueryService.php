<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Sales\Domain\{ReturnSaleRepository, SaleRepository};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ReturnSaleId, SaleId};

final readonly class ReturnSaleQueryService
{
    public function __construct(private ReturnSaleRepository $returns, private SaleRepository $sales, private AuthorizationService $authorization, private ReturnSaleViewFactory $views) {}

    public function get(ActorContext $actor, ReturnSaleId $returnSaleId): ReturnSaleView
    {
        $return = $this->returns->get($actor->organizationId(), $returnSaleId);
        $this->authorization->authorize($actor, PermissionCode::SaleReturnRead, ResourceScope::store($return->organizationId(), $return->storeId()));

        return $this->views->create($return);
    }

    /** @return list<ReturnSaleView> */
    public function findBySale(ActorContext $actor, SaleId $saleId): array
    {
        $sale = $this->sales->get($actor->organizationId(), $saleId);
        $this->authorization->authorize($actor, PermissionCode::SaleReturnRead, ResourceScope::store($sale->organizationId(), $sale->storeId()));

        return array_map($this->views->create(...), $this->returns->findBySale($actor->organizationId(), $saleId));
    }
}
