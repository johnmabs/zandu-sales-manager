<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\DeactivateCategory;

use Zandu\Modules\Catalog\Application\TenantCategoryLoader;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class DeactivateCategoryHandler
{
    public function __construct(
        private TenantCategoryLoader $loader,
        private CategoryRepository $categories,
        private Clock $clock,
        private TenantTransaction $transaction,
    ) {}

    public function __invoke(DeactivateCategory $command): Category
    {
        return $this->transaction->transactional($command->actorContext->organizationId(), function () use ($command): Category {
            $category = $this->loader->get($command->categoryId, $command->actorContext);
            $category->deactivate($command->actorContext->actorId(), $this->clock->now());
            $this->categories->save($category);

            return $category;
        });
    }
}
