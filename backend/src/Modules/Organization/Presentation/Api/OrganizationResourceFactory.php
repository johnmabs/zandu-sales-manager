<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use Zandu\Modules\Organization\Domain\Organization;

final readonly class OrganizationResourceFactory
{
    public function fromAggregate(Organization $organization): OrganizationResource
    {
        return new OrganizationResource(
            $organization->id()->toString(),
            $organization->name()->value(),
            $organization->status()->value,
            $organization->countryCode()->value(),
            $organization->defaultCurrency()->code(),
            $organization->defaultTimeZone()->value(),
            $organization->defaultLocale()->value(),
            $organization->updatedAt()->format(DATE_ATOM),
            $organization->version(),
        );
    }
}
