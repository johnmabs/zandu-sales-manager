<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use Zandu\Modules\Organization\Application\Contract\OrganizationSummary;
use Zandu\Modules\Organization\Application\Contract\OrganizationSummaryProvider;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\SharedKernel\Identity\OrganizationId;

final readonly class RepositoryOrganizationSummaryProvider implements OrganizationSummaryProvider
{
    public function __construct(private OrganizationRepository $organizations) {}

    public function get(OrganizationId $organizationId): OrganizationSummary
    {
        $organization = $this->organizations->get($organizationId);

        return new OrganizationSummary(
            $organization->id()->toString(),
            $organization->name()->value(),
            $organization->status()->value,
            $organization->defaultCurrency()->code(),
            $organization->defaultTimeZone()->value(),
            $organization->defaultLocale()->value(),
        );
    }
}
