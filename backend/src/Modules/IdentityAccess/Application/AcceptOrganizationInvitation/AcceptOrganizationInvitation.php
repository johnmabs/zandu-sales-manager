<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation;

use Zandu\SharedKernel\Context\ActorContext;

final readonly class AcceptOrganizationInvitation
{
    public function __construct(public string $rawToken, public ActorContext $actorContext) {}
}
