<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

final readonly class AcceptedInvitationResource
{
    public function __construct(
        public string $membershipId,
        public string $organizationId,
        public string $userId,
        public string $status,
        public int $authorizationVersion,
    ) {}
}
