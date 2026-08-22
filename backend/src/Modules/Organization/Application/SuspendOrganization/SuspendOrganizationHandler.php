<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\SuspendOrganization;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\TenantOrganizationLoader;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class SuspendOrganizationHandler
{
    public function __construct(
        private TenantOrganizationLoader $loader,
        private OrganizationRepository $organizations,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
    ) {}

    public function __invoke(SuspendOrganization $command): Organization
    {
        return $this->transaction->transactional(
            $command->actorContext->organizationId(),
            function () use ($command): Organization {
                $organization = $this->loader->get($command->organizationId, $command->actorContext);
                $this->authorization->authorize($command->actorContext, PermissionCode::OrganizationSuspend, ResourceScope::organization($organization->id()));
                $this->operationalGuard->assertTenant($command->actorContext);
                $now = $this->clock->now();
                $organization->suspend($command->actorContext->actorId(), $now);
                $this->organizations->save($organization);
                $this->audit->recordSuccess($command->actorContext, SecurityAction::OrganizationSuspended, ResourceReference::for('organization', $organization->id()), SafeAuditMetadata::empty(), $now);

                return $organization;
            },
        );
    }
}
