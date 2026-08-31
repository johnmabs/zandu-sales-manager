<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseReturnId;

final readonly class PurchaseReturnQueryService
{
    public function __construct(
        private PurchaseReturnRepository $purchaseReturns,
        private AuthorizationService $authorization,
        private PurchaseReturnViewFactory $views,
    ) {}

    public function get(ActorContext $actor, PurchaseReturnId $purchaseReturnId): PurchaseReturnView
    {
        $return = $this->purchaseReturns->get($actor->organizationId(), $purchaseReturnId);
        $this->authorization->authorize(
            $actor,
            PermissionCode::PurchaseReturnRead,
            ResourceScope::store($return->organizationId(), $return->sourceStoreId()),
        );

        return $this->views->create($return);
    }
}
