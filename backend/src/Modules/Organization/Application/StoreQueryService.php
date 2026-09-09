<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\SharedKernel\Access\AuthorizationDenied;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class StoreQueryService
{
    public function __construct(
        private StoreRepository $stores,
        private TenantStoreLoader $loader,
        private AuthorizationService $authorization,
        private StoreViewFactory $views,
    ) {}

    /** @return list<StoreView> */
    public function list(ActorContext $actor): array
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::StoreRead, ResourceScope::organization($organizationId));

        $visible = [];
        foreach ($this->stores->findAll($organizationId) as $store) {
            try {
                $this->authorization->authorize(
                    $actor,
                    PermissionCode::StoreRead,
                    ResourceScope::store($organizationId, $store->id()),
                );
            } catch (AuthorizationDenied) {
                continue;
            }
            $visible[] = $this->views->fromAggregate($store);
        }

        return $visible;
    }

    public function get(StoreId $id, ActorContext $actor): StoreView
    {
        $store = $this->loader->get($id, $actor);
        $this->authorization->authorize($actor, PermissionCode::StoreRead, ResourceScope::store($store->organizationId(), $id));

        return $this->views->fromAggregate($store);
    }
}
