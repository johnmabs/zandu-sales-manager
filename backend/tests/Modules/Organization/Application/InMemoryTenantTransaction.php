<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final class InMemoryTenantTransaction implements TenantTransaction
{
    public ?OrganizationId $lastOrganizationId = null;

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        $this->lastOrganizationId = $organizationId;

        return $operation();
    }
}
