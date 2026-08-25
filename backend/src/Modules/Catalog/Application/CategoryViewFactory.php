<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\Category\Category;

final readonly class CategoryViewFactory
{
    public function fromAggregate(Category $category): CategoryView
    {
        return new CategoryView(
            $category->id()->toString(),
            $category->organizationId()->toString(),
            $category->name()->value(),
            $category->parentCategoryId()?->toString(),
            $category->status()->value,
            $category->createdAt()->format(DATE_ATOM),
            $category->updatedAt()?->format(DATE_ATOM),
            $category->version(),
        );
    }
}
