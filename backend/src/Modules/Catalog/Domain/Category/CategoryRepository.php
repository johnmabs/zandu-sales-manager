<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Category;

use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;

interface CategoryRepository
{
    public function save(Category $category): void;

    /** @throws CategoryNotFound */
    public function get(OrganizationId $organizationId, CategoryId $categoryId): Category;

    public function find(OrganizationId $organizationId, CategoryId $categoryId): ?Category;

    /** @return list<Category> */
    public function findAll(OrganizationId $organizationId): array;

    /** @return list<Category> Immediate parent first, up to the root. */
    public function findAncestors(OrganizationId $organizationId, CategoryId $categoryId): array;
}
