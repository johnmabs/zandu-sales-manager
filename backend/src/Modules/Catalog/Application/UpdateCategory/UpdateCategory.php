<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\UpdateCategory;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

final readonly class UpdateCategory
{
    public function __construct(
        public CategoryId $categoryId,
        public string $name,
        public ExpectedVersion $expectedVersion,
        public ActorContext $actorContext,
    ) {}
}
