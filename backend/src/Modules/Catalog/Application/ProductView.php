<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

final readonly class ProductView
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $productCode,
        public string $name,
        public ?string $description,
        public string $status,
        public string $type,
        public string $baseUnitId,
        public bool $inventoryTracked,
        public ?string $taxCategoryId,
        public ?string $categoryId,
        public string $createdAt,
        public ?string $activatedAt,
        public ?string $updatedAt,
        public int $version,
    ) {}
}
