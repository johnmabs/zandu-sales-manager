<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\MoveCategory;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;

final readonly class MoveCategory
{
    public function __construct(
        public CategoryId $categoryId,
        public ?CategoryId $parentCategoryId,
        public ActorContext $actorContext,
    ) {}
}
