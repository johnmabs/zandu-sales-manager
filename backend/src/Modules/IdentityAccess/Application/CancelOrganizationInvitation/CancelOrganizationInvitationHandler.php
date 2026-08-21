<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\CancelOrganizationInvitation;

use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\Modules\Organization\Application\Contract\MemberInvitationPolicy;
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
    ) {}

    public function __invoke(CancelOrganizationInvitation $command): OrganizationInvitation
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): OrganizationInvitation {
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
