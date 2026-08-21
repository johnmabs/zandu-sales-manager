<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\RequestOrganizationClosure;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;

final readonly class RequestOrganizationClosure
{
    public function __construct(
        public OrganizationId $organizationId,
        public ActorContext $actorContext,
    ) {}
}
