<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;

interface OrganizationOnboardingProvisioner
{
    public function provision(
        OrganizationId $organizationId,
        ActorId $actorId,
        string $name,
        string $countryCode,
        string $defaultCurrency,
        string $defaultTimeZone,
        string $defaultLocale,
        DateTimeImmutable $occurredAt,
    ): void;
}
