<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

final readonly class CreatedInvitationView
{
    public function __construct(public InvitationView $invitation, public string $token) {}
}
