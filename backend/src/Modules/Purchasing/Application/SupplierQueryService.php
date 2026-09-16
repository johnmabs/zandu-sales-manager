<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class SupplierQueryService
{
    public function __construct(private SupplierRepository $suppliers, private AuthorizationService $authorization, private SupplierViewFactory $views) {}

    public function get(ActorContext $actor, SupplierId $supplierId): SupplierView
    {
        $this->authorize($actor);

        return $this->views->create($this->suppliers->get($actor->organizationId(), $supplierId));
    }

    /** @return list<SupplierView> */
    public function list(ActorContext $actor): array
    {
        $this->authorize($actor);

        return array_map($this->views->create(...), $this->suppliers->findAll($actor->organizationId()));
    }

    private function authorize(ActorContext $actor): void
    {
        $this->authorization->authorize($actor, PermissionCode::SupplierRead, ResourceScope::organization($actor->organizationId()));
    }
}
