<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\DeactivateCategory;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;

final readonly class DeactivateCategory
{
    public function __construct(
        public CategoryId $categoryId,
        public ActorContext $actorContext,
    ) {}
}
