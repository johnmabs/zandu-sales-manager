<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

final readonly class ProductCreateInput
{
    public function __construct(
        public string $productCode,
        public string $name,
        public ?string $description,
        public string $type,
        public string $baseUnitId,
        public bool $inventoryTracked,
        public ?string $categoryId,
        public ?string $taxCategoryId,
    ) {}
}
