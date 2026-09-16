<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;

#[ORM\Entity]
#[ORM\Table(name: 'organization_invitations', schema: 'identity_access')]
final class OrganizationInvitationRecord
{
    /** @param non-empty-list<array{roleCode: string, storeIds: list<string>}> $intendedRoleAssignments */
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(length: 254)]
        private string $email,
        #[ORM\Column(type: 'guid')]
        private string $invitedBy,
        #[ORM\Column(length: 64, unique: true)]
        private string $tokenHash,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $expiresAt,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(type: 'json')]
        private array $intendedRoleAssignments,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $acceptedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $acceptedAt,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(OrganizationInvitation $invitation): self
    {
        $assignments = array_map(static fn($assignment): array => [
            'roleCode' => $assignment->roleCode(),
            'storeIds' => array_map(static fn($storeId): string => $storeId->toString(), $assignment->storeIds()),
        ], $invitation->intendedRoleAssignments());

        return new self(
            $invitation->id()->toString(),
            $invitation->organizationId()->toString(),
            $invitation->email()->value(),
            $invitation->invitedBy()->toString(),
            $invitation->tokenHash(),
            $invitation->expiresAt(),
            $invitation->status()->value,
            $assignments,
            $invitation->acceptedBy()?->toString(),
            $invitation->acceptedAt(),
            $invitation->version(),
        );
    }

    public function synchronize(OrganizationInvitation $invitation): void
    {
        $current = self::fromAggregate($invitation);
        $this->status = $current->status;
        $this->acceptedBy = $current->acceptedBy;
        $this->acceptedAt = $current->acceptedAt;
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function email(): string
    {
        return $this->email;
    }
    public function invitedBy(): string
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
    public function status(): string
    {
        return $this->status;
    }
    /** @return non-empty-list<array{roleCode: string, storeIds: list<string>}> */
    public function intendedRoleAssignments(): array
    {
        return $this->intendedRoleAssignments;
    }
    public function acceptedBy(): ?string
    {
        return $this->acceptedBy;
    }
    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }
    public function version(): int
    {
        return $this->version;
    }
}
