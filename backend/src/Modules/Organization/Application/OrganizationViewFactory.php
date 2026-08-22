<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Domain\Organization;

final readonly class OrganizationViewFactory
{
    public function fromAggregate(Organization $organization): OrganizationView
    {
        return new OrganizationView(
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
