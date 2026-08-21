<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember;

use DateInterval;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\InvitationTokenService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\IdentityAccess\Domain\Access\LastOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Invitation\ActiveInvitationAlreadyExists;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\Modules\Organization\Application\Contract\MemberInvitationPolicy;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class InviteOrganizationMemberHandler
{
    public function __construct(
        private OrganizationInvitationRepository $invitations,
        private MemberInvitationPolicy $policy,
        private InvitationTokenService $tokens,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private TenantTransaction $transaction,
        private SystemRoleCatalog $systemRoles,
        private LastOrganizationOwner $lastOwner,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(InviteOrganizationMember $command): CreatedOrganizationInvitation
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): CreatedOrganizationInvitation {
            $this->authorization->authorize($command->actorContext, PermissionCode::MemberInvite, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($command->actorContext);
            $now = $this->clock->now();
            $email = InvitationEmail::fromString($command->email);
            $storesById = [];
            foreach ($command->intendedRoleAssignments as $assignment) {
                $role = $this->systemRoles->get(RoleCode::fromString($assignment->roleCode()));
                if ($this->systemRoles->isOrganizationOwner($role->id())) {
                    $this->lastOwner->assertActiveOwner($command->actorContext);
                }
                foreach ($assignment->storeIds() as $storeId) {
                    $storesById[$storeId->toString()] = $storeId;
                }
            }
            /** @var list<StoreId> $selectedStoreIds */
            $selectedStoreIds = array_values($storesById);
            $this->policy->assertCanInvite($command->actorContext, $selectedStoreIds);
            if ($this->invitations->pendingExists($organizationId, $email, $now)) {
                throw ActiveInvitationAlreadyExists::forEmail($email);
            }

            $token = $this->tokens->issue($organizationId);
            $invitation = OrganizationInvitation::invite(
                OrganizationInvitationId::generate($this->idGenerator),
                $organizationId,
                $email,
                $command->actorContext->actorId(),
                $token->tokenHash(),
                $command->expiresAt ?? $now->add(new DateInterval('P7D')),
                $command->intendedRoleAssignments,
                $now,
            );
            $this->invitations->save($invitation);

            return new CreatedOrganizationInvitation($invitation, $token->reveal());
        });
    }
}
