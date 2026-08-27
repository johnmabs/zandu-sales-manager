<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Sales\Domain\{SaleRepository,SaleStatus,SalesRuleViolation};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SaleId;

final readonly class SaleQueryService
{
    public function __construct(private SaleRepository $sales, private AuthorizationService $authorization, private SaleViewFactory $views) {}

    public function get(ActorContext $actor, SaleId $saleId, bool $receipt = false): SaleView
    {
        $sale = $this->sales->get($actor->organizationId(), $saleId);
        $this->authorization->authorize($actor, PermissionCode::SaleRead, ResourceScope::store($sale->organizationId(), $sale->storeId()));
        if ($receipt && SaleStatus::Completed !== $sale->status()) {
            throw SalesRuleViolation::with('SALE_NOT_COMPLETED', 'A receipt is only available for a completed sale.');
        }

        return $this->views->create($sale);
    }
}
