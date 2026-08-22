<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

final readonly class AcceptedInvitationView
{
    public function __construct(
        public string $membershipId,
        public string $organizationId,
        public string $userId,
        public string $status,
        public int $authorizationVersion,
    ) {}
}
