<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use Zandu\Modules\Organization\Application\OrganizationView;

final readonly class OrganizationResourceFactory
{
    public function fromView(OrganizationView $organization): OrganizationResource
    {
        return new OrganizationResource(
            $organization->id,
            $organization->name,
            $organization->status,
            $organization->countryCode,
            $organization->defaultCurrency,
            $organization->defaultTimeZone,
            $organization->defaultLocale,
            $organization->updatedAt,
            $organization->version,
        );
    }
}
