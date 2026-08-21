<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

use Zandu\SharedKernel\Identity\OrganizationId;

interface OrganizationRepository
{
    public function save(Organization $organization): void;

    /** @throws OrganizationNotFound */
    public function get(OrganizationId $id): Organization;

    public function find(OrganizationId $id): ?Organization;
}
