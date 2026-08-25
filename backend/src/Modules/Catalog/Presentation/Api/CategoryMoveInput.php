<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

final readonly class CategoryMoveInput
{
    public function __construct(public ?string $parentCategoryId = null) {}
}
