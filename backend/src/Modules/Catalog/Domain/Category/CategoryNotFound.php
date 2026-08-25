<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Category;

use RuntimeException;
use Zandu\SharedKernel\Identity\CategoryId;

final class CategoryNotFound extends RuntimeException
{
    public static function withId(CategoryId $id): self
    {
        return new self(sprintf('Category "%s" was not found.', $id->toString()));
    }
}
