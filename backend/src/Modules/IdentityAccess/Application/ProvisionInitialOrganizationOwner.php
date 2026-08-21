<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use LogicException;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\Contract\InitialOrganizationOwnerProvisioner;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;

final readonly class ProvisionInitialOrganizationOwner implements InitialOrganizationOwnerProvisioner
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private SystemRoleCatalog $systemRoles,
        private IdGenerator $idGenerator,
    ) {}

    public function provision(OrganizationId $organizationId, ActorContext $actorContext, DateTimeImmutable $occurredAt): void
    {
        $userId = $actorContext->userId();
        if (null === $userId) {
            throw new LogicException('A user identity is required to provision the initial organization owner.');
        }

        $ownerRoleId = $this->systemRoles->organizationOwnerRoleId();
        $assignment = RoleAssignment::assign(
            $ownerRoleId,
            AccessScope::organization($organizationId),
            $actorContext->actorId(),
            $occurredAt,
        );
        $membership = OrganizationMembership::activateInitialOwner(
            OrganizationMembershipId::generate($this->idGenerator),
            $organizationId,
            $userId,
            $assignment,
            $actorContext->actorId(),
            $occurredAt,
        );
        $this->memberships->save($membership);
    }
}
