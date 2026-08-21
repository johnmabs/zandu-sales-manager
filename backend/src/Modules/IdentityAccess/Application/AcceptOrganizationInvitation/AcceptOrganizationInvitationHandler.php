<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation;

use LogicException;
use Zandu\Modules\IdentityAccess\Application\Contract\InvitationTokenService;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Access\ScopedStore;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class AcceptOrganizationInvitationHandler
{
    public function __construct(
        private OrganizationInvitationRepository $invitations,
        private OrganizationMembershipRepository $memberships,
        private InvitationTokenService $tokens,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private SystemRoleCatalog $systemRoles,
    ) {}

    public function __invoke(AcceptOrganizationInvitation $command): OrganizationMembership
    {
        $organizationId = $this->tokens->organizationId($command->rawToken);
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): OrganizationMembership {
            $userId = $command->actorContext->userId();
            $authenticatedEmail = $command->actorContext->email();
            if (null === $userId || null === $authenticatedEmail) {
                throw new LogicException('An authenticated user identity is required to accept an invitation.');
            }
            $invitation = $this->invitations->getByTokenHash($organizationId, $this->tokens->hash($command->rawToken));
            if (!$invitation->email()->equals(InvitationEmail::fromString($authenticatedEmail))) {
                throw new LogicException('The authenticated email does not match the invitation.');
            }
            $now = $this->clock->now();
            $roleAssignments = array_map(function ($intended) use ($invitation, $organizationId, $now): RoleAssignment {
                $role = $this->systemRoles->get(RoleCode::fromString($intended->roleCode()));
                $storeIds = $intended->storeIds();
                $scope = [] === $storeIds
                    ? AccessScope::organization($organizationId)
                    : AccessScope::selectedStores($organizationId, array_map(
                        static fn($storeId): ScopedStore => new ScopedStore($storeId, $organizationId),
                        $storeIds,
                    ));

                return RoleAssignment::assign($role->id(), $scope, $invitation->invitedBy(), $now);
            }, $invitation->intendedRoleAssignments());
            $membership = $this->memberships->findByUser($organizationId, $userId);
            if (null === $membership) {
                $membership = OrganizationMembership::activateFromInvitation(
                    OrganizationMembershipId::generate($this->idGenerator),
                    $organizationId,
                    $userId,
                    $roleAssignments,
                    $command->actorContext->actorId(),
                    $now,
                );
            } else {
                $membership->activateFromInvitationAgain(
                    $roleAssignments,
                    $command->actorContext->actorId(),
                    $now,
                );
            }
            $invitation->accept($userId, $command->actorContext->actorId(), $now);
            $this->memberships->save($membership);
            $this->invitations->save($invitation);
            return $membership;
        });
    }
}
