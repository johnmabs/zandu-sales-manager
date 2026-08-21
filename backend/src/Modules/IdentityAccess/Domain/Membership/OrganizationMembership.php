<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Membership;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\UserId;

final class OrganizationMembership
{
    /** @param non-empty-list<IntendedRoleAssignment> $roleAssignments */
    private function __construct(
        private readonly OrganizationMembershipId $id,
        private readonly OrganizationId $organizationId,
        private readonly UserId $userId,
        private MembershipStatus $status,
        private array $roleAssignments,
        private int $authorizationVersion,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ActorId $updatedBy,
        private DateTimeImmutable $updatedAt,
        private ?ActorId $suspendedBy,
        private ?DateTimeImmutable $suspendedAt,
        private ?ActorId $revokedBy,
        private ?DateTimeImmutable $revokedAt,
        private int $version,
    ) {}

    /** @param non-empty-list<IntendedRoleAssignment> $roleAssignments */
    public static function activateFromInvitation(
        OrganizationMembershipId $id,
        OrganizationId $organizationId,
        UserId $userId,
        array $roleAssignments,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        $occurredAt = self::utc($occurredAt);
        return new self($id, $organizationId, $userId, MembershipStatus::Active, $roleAssignments, 1, $actorId, $occurredAt, $actorId, $occurredAt, null, null, null, null, 1);
    }

    /** @param non-empty-list<IntendedRoleAssignment> $roleAssignments */
    public static function reconstitute(
        OrganizationMembershipId $id,
        OrganizationId $organizationId,
        UserId $userId,
        MembershipStatus $status,
        array $roleAssignments,
        int $authorizationVersion,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
        ActorId $updatedBy,
        DateTimeImmutable $updatedAt,
        ?ActorId $suspendedBy,
        ?DateTimeImmutable $suspendedAt,
        ?ActorId $revokedBy,
        ?DateTimeImmutable $revokedAt,
        int $version,
    ): self {
        return new self($id, $organizationId, $userId, $status, $roleAssignments, $authorizationVersion, $createdBy, $createdAt, $updatedBy, $updatedAt, $suspendedBy, $suspendedAt, $revokedBy, $revokedAt, $version);
    }

    /** @param non-empty-list<IntendedRoleAssignment> $roleAssignments */
    public function activateFromInvitationAgain(array $roleAssignments, ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (!in_array($this->status, [MembershipStatus::Invited, MembershipStatus::Suspended], true)) {
            throw new LogicException('Only an invited or suspended membership can be activated from an invitation.');
        }
        $this->status = MembershipStatus::Active;
        $this->roleAssignments = $roleAssignments;
        $this->suspendedBy = null;
        $this->suspendedAt = null;
        $this->changedBy($actorId, $occurredAt);
    }

    public function suspend(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(MembershipStatus::Active, 'Only an active membership can be suspended.');
        $occurredAt = self::utc($occurredAt);
        $this->status = MembershipStatus::Suspended;
        $this->suspendedBy = $actorId;
        $this->suspendedAt = $occurredAt;
        $this->changedBy($actorId, $occurredAt);
    }

    public function reactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(MembershipStatus::Suspended, 'Only a suspended membership can be reactivated.');
        $this->status = MembershipStatus::Active;
        $this->suspendedBy = null;
        $this->suspendedAt = null;
        $this->changedBy($actorId, $occurredAt);
    }

    public function revoke(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (!in_array($this->status, [MembershipStatus::Active, MembershipStatus::Suspended], true)) {
            throw new LogicException('Only an active or suspended membership can be revoked.');
        }
        $occurredAt = self::utc($occurredAt);
        $this->status = MembershipStatus::Revoked;
        $this->revokedBy = $actorId;
        $this->revokedAt = $occurredAt;
        $this->changedBy($actorId, $occurredAt);
    }

    public function id(): OrganizationMembershipId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function userId(): UserId
    {
        return $this->userId;
    }
    public function status(): MembershipStatus
    {
        return $this->status;
    }
    /** @return non-empty-list<IntendedRoleAssignment> */
    public function roleAssignments(): array
    {
        return $this->roleAssignments;
    }
    public function hasRole(RoleCode $roleCode): bool
    {
        foreach ($this->roleAssignments as $assignment) {
            if ($assignment->matches($roleCode)) {
                return true;
            }
        }

        return false;
    }
    public function authorizationVersion(): int
    {
        return $this->authorizationVersion;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function updatedBy(): ActorId
    {
        return $this->updatedBy;
    }
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function suspendedBy(): ?ActorId
    {
        return $this->suspendedBy;
    }
    public function suspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }
    public function revokedBy(): ?ActorId
    {
        return $this->revokedBy;
    }
    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }
    public function version(): int
    {
        return $this->version;
    }

    private function changedBy(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->updatedBy = $actorId;
        $this->updatedAt = self::utc($occurredAt);
        ++$this->authorizationVersion;
        ++$this->version;
    }
    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
    private function requireStatus(MembershipStatus $status, string $message): void
    {
        if ($this->status !== $status) {
            throw new LogicException($message);
        }
    }
}
