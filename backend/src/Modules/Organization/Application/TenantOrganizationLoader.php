<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationNotFound;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;

final readonly class TenantOrganizationLoader
{
    public function __construct(private OrganizationRepository $organizations) {}

    public function get(OrganizationId $organizationId, ActorContext $actorContext): Organization
    {
        if (!$organizationId->equals($actorContext->organizationId())) {
            throw OrganizationNotFound::withId($organizationId);
        }

        return $this->organizations->get($organizationId);
    }
}
