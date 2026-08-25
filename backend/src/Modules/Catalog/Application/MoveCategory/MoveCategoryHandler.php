<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\MoveCategory;

use Zandu\Modules\Catalog\Application\TenantCategoryLoader;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class MoveCategoryHandler
{
    public function __construct(
        private TenantCategoryLoader $loader,
        private CategoryRepository $categories,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(MoveCategory $command): Category
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): Category {
            $this->categories->lockHierarchy($organizationId);
            $category = $this->loader->get($command->categoryId, $command->actorContext);
            $parent = null !== $command->parentCategoryId
                ? $this->categories->get($organizationId, $command->parentCategoryId)
                : null;
            $parentAncestorIds = null !== $parent
                ? array_map(
                    static fn(Category $ancestor) => $ancestor->id(),
                    $this->categories->findAncestors($organizationId, $parent->id()),
                )
                : [];
            $category->moveTo(
                $parent,
                $parentAncestorIds,
                $command->actorContext->actorId(),
                $this->clock->now(),
            );
            $this->categories->save($category);

            return $category;
        });
    }
}
