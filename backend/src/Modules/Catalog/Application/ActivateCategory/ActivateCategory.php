<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\ActivateCategory;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;

final readonly class ActivateCategory
{
    public function __construct(
        public CategoryId $categoryId,
        public ActorContext $actorContext,
    ) {}
}
