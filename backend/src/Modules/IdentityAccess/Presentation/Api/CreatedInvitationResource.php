<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

final readonly class CreatedInvitationResource
{
    public function __construct(public InvitationResource $invitation, public string $token) {}
}
