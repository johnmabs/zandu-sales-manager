<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\IdentityAccess\Domain\Invitation\Event\OrganizationInvitationAccepted;
use Zandu\Modules\IdentityAccess\Domain\Invitation\Event\OrganizationInvitationEvent;
use Zandu\Modules\IdentityAccess\Domain\Invitation\Event\OrganizationMemberInvited;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class OrganizationInvitation implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<OrganizationInvitationEvent> */
    private array $recordedEvents = [];

    /** @param non-empty-list<IntendedRoleAssignment> $intendedRoleAssignments */
    private function __construct(
        private readonly OrganizationInvitationId $id,
        private readonly OrganizationId $organizationId,
        private readonly InvitationEmail $email,
        private readonly ActorId $invitedBy,
        private readonly string $tokenHash,
        private readonly DateTimeImmutable $expiresAt,
        private InvitationStatus $status,
        private readonly array $intendedRoleAssignments,
        private ?UserId $acceptedBy,
        private ?DateTimeImmutable $acceptedAt,
        private int $version,
    ) {
        $this->assertValidVersion();
    }

    /** @param list<IntendedRoleAssignment> $intendedRoleAssignments */
    public static function invite(
        OrganizationInvitationId $id,
        OrganizationId $organizationId,
        InvitationEmail $email,
        ActorId $invitedBy,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        array $intendedRoleAssignments,
        DateTimeImmutable $occurredAt,
    ): self {
        $occurredAt = self::utc($occurredAt);
        $expiresAt = self::utc($expiresAt);
        if ($expiresAt <= $occurredAt) {
            throw new InvalidArgumentException('Invitation expiration must be in the future.');
        }
        if ('' === $tokenHash) {
            throw new InvalidArgumentException('Invitation token hash is required.');
        }
        if ([] === $intendedRoleAssignments) {
            throw new InvalidArgumentException('At least one intended role assignment is required.');
        }

        $invitation = new self(
            $id,
            $organizationId,
            $email,
            $invitedBy,
            $tokenHash,
            $expiresAt,
            InvitationStatus::Pending,
            $intendedRoleAssignments,
            null,
            null,
            1,
        );
        $invitation->recordedEvents[] = new OrganizationMemberInvited($id, $organizationId, $invitedBy, $occurredAt);

        return $invitation;
    }

    /** @param non-empty-list<IntendedRoleAssignment> $intendedRoleAssignments */
    public static function reconstitute(
        OrganizationInvitationId $id,
        OrganizationId $organizationId,
        InvitationEmail $email,
        ActorId $invitedBy,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        InvitationStatus $status,
        array $intendedRoleAssignments,
        ?UserId $acceptedBy,
        ?DateTimeImmutable $acceptedAt,
        int $version,
    ): self {
        return new self($id, $organizationId, $email, $invitedBy, $tokenHash, $expiresAt, $status, $intendedRoleAssignments, $acceptedBy, $acceptedAt, $version);
    }

    public function accept(UserId $userId, ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $occurredAt = self::utc($occurredAt);
        $this->expireWhenDue($occurredAt);
        $this->requirePending('Only a pending invitation can be accepted.');
        $this->status = InvitationStatus::Accepted;
        $this->acceptedBy = $userId;
        $this->acceptedAt = $occurredAt;
        $this->advanceVersion();
        $this->recordedEvents[] = new OrganizationInvitationAccepted($this->id, $this->organizationId, $actorId, $occurredAt);
    }

    public function cancel(DateTimeImmutable $occurredAt): void
    {
        $this->expireWhenDue(self::utc($occurredAt));
        $this->requirePending('Only a pending invitation can be cancelled.');
        $this->status = InvitationStatus::Cancelled;
        $this->advanceVersion();
    }

    public function expireWhenDue(DateTimeImmutable $occurredAt): bool
    {
        if (InvitationStatus::Pending === $this->status && self::utc($occurredAt) >= $this->expiresAt) {
            $this->status = InvitationStatus::Expired;
            $this->advanceVersion();
            return true;
        }
        return false;
    }

    /** @return list<OrganizationInvitationEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }
    public function id(): OrganizationInvitationId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function email(): InvitationEmail
    {
        return $this->email;
    }
    public function invitedBy(): ActorId
    {
        return $this->invitedBy;
    }
    public function tokenHash(): string
    {
        return $this->tokenHash;
    }
    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
    public function status(): InvitationStatus
    {
        return $this->status;
    }
    /** @return non-empty-list<IntendedRoleAssignment> */
    public function intendedRoleAssignments(): array
    {
        return $this->intendedRoleAssignments;
    }
    public function acceptedBy(): ?UserId
    {
        return $this->acceptedBy;
    }
    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    private function requirePending(string $message): void
    {
        if (InvitationStatus::Pending !== $this->status) {
            throw new LogicException($message);
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
