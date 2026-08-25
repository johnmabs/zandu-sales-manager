<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\DeactivateCategory;

use Zandu\Modules\Catalog\Application\TenantCategoryLoader;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class DeactivateCategoryHandler
{
    public function __construct(
        private TenantCategoryLoader $loader,
        private CategoryRepository $categories,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
    ) {}

    public function __invoke(DeactivateCategory $command): Category
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Category {
            $this->authorization->authorize($command->actorContext, PermissionCode::CategoryUpdate, ResourceScope::organization($organizationId));
            $category = $this->loader->get($command->categoryId, $command->actorContext);
            $category->deactivate($command->actorContext->actorId(), $this->clock->now());
            $this->categories->save($category);

            return $category;
        });
    }
}
