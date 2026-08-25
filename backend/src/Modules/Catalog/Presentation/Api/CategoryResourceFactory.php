<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use Zandu\Modules\Catalog\Application\CategoryView;

final readonly class CategoryResourceFactory
{
    public function fromView(CategoryView $category): CategoryResource
    {
        return new CategoryResource(
            $category->id,
            $category->organizationId,
            $category->name,
            $category->parentCategoryId,
            $category->status,
            $category->createdAt,
            $category->updatedAt,
            $category->version,
        );
    }
}
