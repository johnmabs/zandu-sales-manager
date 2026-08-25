<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

final readonly class CategoryCreateInput
{
    public function __construct(
        public string $name,
        public ?string $parentCategoryId = null,
    ) {}
}
