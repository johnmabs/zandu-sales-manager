<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Membership;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
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
        return new self($id, $organizationId, $userId, MembershipStatus::Active, $roleAssignments, 1, $actorId, $occurredAt, $actorId, $occurredAt, 1);
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
        int $version,
    ): self {
        return new self($id, $organizationId, $userId, $status, $roleAssignments, $authorizationVersion, $createdBy, $createdAt, $updatedBy, $updatedAt, $version);
    }

    /** @param non-empty-list<IntendedRoleAssignment> $roleAssignments */
    public function activateFromInvitationAgain(array $roleAssignments, ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (!in_array($this->status, [MembershipStatus::Invited, MembershipStatus::Suspended], true)) {
            throw new LogicException('Only an invited or suspended membership can be activated from an invitation.');
        }
        $this->status = MembershipStatus::Active;
        $this->roleAssignments = $roleAssignments;
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
}
