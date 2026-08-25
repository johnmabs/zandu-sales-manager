<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;

final readonly class TenantCategoryLoader
{
    public function __construct(private CategoryRepository $categories) {}

    public function get(CategoryId $categoryId, ActorContext $actorContext): Category
    {
        return $this->categories->get($actorContext->organizationId(), $categoryId);
    }
}
