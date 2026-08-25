<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

final readonly class CategoryView
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $parentCategoryId,
        public string $status,
        public string $createdAt,
        public ?string $updatedAt,
        public int $version,
    ) {}
}
