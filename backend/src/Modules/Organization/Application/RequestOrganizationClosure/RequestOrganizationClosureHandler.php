<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\RequestOrganizationClosure;

use Zandu\Modules\Organization\Application\TenantOrganizationLoader;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\SharedKernel\Time\Clock;

final readonly class RequestOrganizationClosureHandler
{
    public function __construct(
        private TenantOrganizationLoader $loader,
        private OrganizationRepository $organizations,
        private Clock $clock,
    ) {}

    public function __invoke(RequestOrganizationClosure $command): Organization
    {
        $organization = $this->loader->get($command->organizationId, $command->actorContext);
        $organization->requestClosure($command->actorContext->actorId(), $this->clock->now());
        $this->organizations->save($organization);

        return $organization;
    }
}
