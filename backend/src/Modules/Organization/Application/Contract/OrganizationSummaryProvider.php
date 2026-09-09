<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Identity\OrganizationId;

interface OrganizationSummaryProvider
{
    public function get(OrganizationId $organizationId): OrganizationSummary;
}
