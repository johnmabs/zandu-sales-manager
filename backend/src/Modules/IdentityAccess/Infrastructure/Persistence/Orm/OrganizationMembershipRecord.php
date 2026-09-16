<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;

#[ORM\Entity]
#[ORM\Table(name: 'organization_memberships', schema: 'identity_access')]
#[ORM\UniqueConstraint(name: 'membership_tenant_user_unique', columns: ['organization_id', 'user_id'])]
final class OrganizationMembershipRecord
{
    /** @param non-empty-list<array{roleId:string, scopeType:string, storeIds:list<string>, assignedBy:string, assignedAt:string, expiresAt:?string}> $roleAssignments */
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(type: 'guid')]
        private string $userId,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(type: 'json')]
        private array $roleAssignments,
        #[ORM\Column]
        private int $authorizationVersion,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $updatedBy,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $updatedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $suspendedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $suspendedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $revokedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $revokedAt,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(OrganizationMembership $membership): self
    {
        $assignments = array_map(static fn($assignment): array => [
            'roleId' => $assignment->roleId()->toString(),
            'scopeType' => $assignment->scope()->type()->value,
            'storeIds' => array_map(static fn($id): string => $id->toString(), $assignment->scope()->storeIds()),
            'assignedBy' => $assignment->assignedBy()->toString(),
            'assignedAt' => $assignment->assignedAt()->format(DATE_ATOM),
            'expiresAt' => $assignment->expiresAt()?->format(DATE_ATOM),
        ], $membership->roleAssignments());
        return new self(
            $membership->id()->toString(),
            $membership->organizationId()->toString(),
            $membership->userId()->toString(),
            $membership->status()->value,
            $assignments,
            $membership->authorizationVersion(),
            $membership->createdBy()->toString(),
            $membership->createdAt(),
            $membership->updatedBy()->toString(),
            $membership->updatedAt(),
            $membership->suspendedBy()?->toString(),
            $membership->suspendedAt(),
            $membership->revokedBy()?->toString(),
            $membership->revokedAt(),
            $membership->version(),
        );
    }
    public function synchronize(OrganizationMembership $membership): void
    {
        $current = self::fromAggregate($membership);
        $this->status = $current->status;
        $this->roleAssignments = $current->roleAssignments;
        $this->authorizationVersion = $current->authorizationVersion;
        $this->updatedBy = $current->updatedBy;
        $this->updatedAt = $current->updatedAt;
        $this->suspendedBy = $current->suspendedBy;
        $this->suspendedAt = $current->suspendedAt;
        $this->revokedBy = $current->revokedBy;
        $this->revokedAt = $current->revokedAt;
    }
    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function userId(): string
    {
        return $this->userId;
    }
    public function status(): string
    {
        return $this->status;
    }
    /** @return non-empty-list<array{roleId:string, scopeType:string, storeIds:list<string>, assignedBy:string, assignedAt:string, expiresAt:?string}> */ public function roleAssignments(): array
    {
        return $this->roleAssignments;
    }
    public function authorizationVersion(): int
    {
        return $this->authorizationVersion;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function updatedBy(): string
    {
        return $this->updatedBy;
    }
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function suspendedBy(): ?string
    {
        return $this->suspendedBy;
    }
    public function suspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }
    public function revokedBy(): ?string
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
}
