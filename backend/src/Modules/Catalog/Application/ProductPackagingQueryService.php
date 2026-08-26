<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class ProductPackagingQueryService
{
    public function __construct(private ProductPackagingRepository $packagings, private AuthorizationService $authorization, private ProductPackagingViewFactory $views) {}

    /** @return list<ProductPackagingView> */
    public function list(ProductId $productId, ActorContext $actor): array
    {
        $this->authorization->authorize($actor, PermissionCode::ProductRead, ResourceScope::organization($actor->organizationId()));
        return array_map($this->views->fromAggregate(...), $this->packagings->findAll($actor->organizationId(), $productId));
    }

    public function get(ProductPackagingId $id, ActorContext $actor): ProductPackagingView
    {
        $this->authorization->authorize($actor, PermissionCode::ProductRead, ResourceScope::organization($actor->organizationId()));
        return $this->views->fromAggregate($this->packagings->get($actor->organizationId(), $id));
    }
}
