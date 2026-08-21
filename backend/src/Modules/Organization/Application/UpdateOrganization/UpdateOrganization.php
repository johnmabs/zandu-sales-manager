<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\UpdateOrganization;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationId;

final readonly class UpdateOrganization
{
    public function __construct(
        public OrganizationId $organizationId,
        public string $name,
        public string $countryCode,
        public string $defaultCurrency,
        public string $defaultTimeZone,
        public string $defaultLocale,
        public ActorContext $actorContext,
    ) {}
}
