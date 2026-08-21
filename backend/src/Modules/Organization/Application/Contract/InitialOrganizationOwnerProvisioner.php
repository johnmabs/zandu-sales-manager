<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;

interface InitialOrganizationOwnerProvisioner
{
    public function provision(OrganizationId $organizationId, ActorContext $actorContext, DateTimeImmutable $occurredAt): void;
}
