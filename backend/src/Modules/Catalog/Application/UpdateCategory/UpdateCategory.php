<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\UpdateCategory;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;

final readonly class UpdateCategory
{
    public function __construct(
        public CategoryId $categoryId,
        public string $name,
        public ActorContext $actorContext,
    ) {}
}
