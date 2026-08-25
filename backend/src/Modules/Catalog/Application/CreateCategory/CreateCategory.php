<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\CreateCategory;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;

final readonly class CreateCategory
{
    public function __construct(
        public string $name,
        public ?CategoryId $parentCategoryId,
        public ActorContext $actorContext,
    ) {}
}
