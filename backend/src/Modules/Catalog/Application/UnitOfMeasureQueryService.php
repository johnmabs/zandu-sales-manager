<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final readonly class UnitOfMeasureQueryService
{
    public function __construct(
        private UnitOfMeasureRepository $units,
        private TenantUnitOfMeasureLoader $loader,
        private AuthorizationService $authorization,
        private UnitOfMeasureViewFactory $views,
    ) {}

    /** @return list<UnitOfMeasureView> */
    public function list(ActorContext $actor): array
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::CatalogRead, ResourceScope::organization($organizationId));

        return array_map($this->views->fromAggregate(...), $this->units->findAll($organizationId));
    }

    public function get(UnitOfMeasureId $id, ActorContext $actor): UnitOfMeasureView
    {
        $this->authorization->authorize(
            $actor,
            PermissionCode::CatalogRead,
            ResourceScope::organization($actor->organizationId()),
        );

        return $this->views->fromAggregate($this->loader->get($id, $actor));
    }
}
