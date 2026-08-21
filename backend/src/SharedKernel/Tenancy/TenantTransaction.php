<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Tenancy;

use Zandu\SharedKernel\Identity\OrganizationId;

interface TenantTransaction
{
    /**
     * @template TResult
     *
     * @param callable(): TResult $operation
     *
     * @return TResult
     */
    public function transactional(OrganizationId $organizationId, callable $operation): mixed;
}
