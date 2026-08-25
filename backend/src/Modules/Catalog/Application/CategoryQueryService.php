<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;

final readonly class CategoryQueryService
{
    public function __construct(
        private CategoryRepository $categories,
        private TenantCategoryLoader $loader,
        private AuthorizationService $authorization,
        private CategoryViewFactory $views,
    ) {}

    /** @return list<CategoryView> */
    public function list(ActorContext $actor): array
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::CatalogRead, ResourceScope::organization($organizationId));

        return array_map($this->views->fromAggregate(...), $this->categories->findAll($organizationId));
    }

    public function get(CategoryId $id, ActorContext $actor): CategoryView
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::CatalogRead, ResourceScope::organization($organizationId));

        return $this->views->fromAggregate($this->loader->get($id, $actor));
    }
}
