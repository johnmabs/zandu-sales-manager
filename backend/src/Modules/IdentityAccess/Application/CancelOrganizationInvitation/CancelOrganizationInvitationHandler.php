<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\CancelOrganizationInvitation;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\Modules\Organization\Application\Contract\MemberInvitationPolicy;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CancelOrganizationInvitationHandler
{
    public function __construct(
        private OrganizationInvitationRepository $invitations,
        private MemberInvitationPolicy $policy,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CancelOrganizationInvitation $command): OrganizationInvitation
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): OrganizationInvitation {
            $this->authorization->authorize($command->actorContext, PermissionCode::MemberInvite, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext, OperationalMode::Remediation);
            $invitation = $this->invitations->get($organizationId, $command->invitationId);
            $stores = [];
            foreach ($invitation->intendedRoleAssignments() as $assignment) {
                foreach ($assignment->storeIds() as $storeId) {
                    $stores[$storeId->toString()] = $storeId;
                }
            }
            /** @var list<StoreId> $storeIds */
            $storeIds = array_values($stores);
            $this->policy->assertCanInvite($command->actorContext, $storeIds);
            $invitation->cancel($this->clock->now());
            $this->invitations->save($invitation);
            return $invitation;
        });
    }
}
