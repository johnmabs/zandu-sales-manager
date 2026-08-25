<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

final readonly class CategoryUpdateInput
{
    public function __construct(public string $name) {}
}
