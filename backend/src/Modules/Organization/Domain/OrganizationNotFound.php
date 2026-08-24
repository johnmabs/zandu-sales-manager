<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\OrganizationId;

final class OrganizationNotFound extends RuntimeException implements ResourceNotFound
{
    public static function withId(OrganizationId $id): self
    {
        return new self(sprintf('Organization "%s" was not found.', $id->toString()));
    }
}
