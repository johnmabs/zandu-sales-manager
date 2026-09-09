<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

final readonly class CurrentSessionView
{
    /** @param list<CurrentOrganizationView> $organizations */
    public function __construct(
        public string $id,
        public string $userId,
        public string $organizationId,
        public int $authorizationVersion,
        public EffectiveAccessView $effectiveAccess,
        public ?string $email,
        public array $organizations,
    ) {}
}
