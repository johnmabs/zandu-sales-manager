<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\UpdateCategory;

use Zandu\Modules\Catalog\Application\TenantCategoryLoader;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryName;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class UpdateCategoryHandler
{
    public function __construct(
        private TenantCategoryLoader $loader,
        private CategoryRepository $categories,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(UpdateCategory $command): Category
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Category {
            $this->authorization->authorize($command->actorContext, PermissionCode::CategoryUpdate, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $category = $this->loader->get($command->categoryId, $command->actorContext);
            $category->update(
                CategoryName::fromString($command->name),
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->categories->save($category);

            return $category;
        });
    }
}
