<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\ReactivateOrganization;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Application\TenantOrganizationLoader;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ReactivateOrganizationHandler
{
    public function __construct(
        private TenantOrganizationLoader $loader,
        private OrganizationRepository $organizations,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(ReactivateOrganization $command): Organization
    {
        return $this->transaction->transactional(
            $command->actorContext->organizationId(),
            function () use ($command): Organization {
                $organization = $this->loader->get($command->organizationId, $command->actorContext);
                $this->authorization->authorize($command->actorContext, PermissionCode::OrganizationSuspend, ResourceScope::organization($organization->id()));
                $this->operationalGuard->assertTenant($command->actorContext, OperationalMode::Remediation);
                $organization->reactivate($command->actorContext->actorId(), $this->clock->now());
                $this->organizations->save($organization);

                return $organization;
            },
        );
    }
}
