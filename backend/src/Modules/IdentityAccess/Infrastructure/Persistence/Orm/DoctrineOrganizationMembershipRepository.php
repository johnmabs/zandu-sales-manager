<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipNotFound;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineOrganizationMembershipRepository implements OrganizationMembershipRepository
{
    public function __construct(private EntityManagerInterface $entityManager, private UuidFactory $uuidFactory) {}
    public function save(OrganizationMembership $membership): void
    {
        $record = $this->entityManager->find(OrganizationMembershipRecord::class, $membership->id()->toString());
        $record instanceof OrganizationMembershipRecord ? $record->synchronize($membership) : $this->entityManager->persist(OrganizationMembershipRecord::fromAggregate($membership));
        $this->entityManager->flush();
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership
    {
        $record = $this->entityManager->getRepository(OrganizationMembershipRecord::class)->findOneBy([
            'organizationId' => $organizationId->toString(), 'userId' => $userId->toString(),
        ]);
        return $record instanceof OrganizationMembershipRecord ? $this->toAggregate($record) : null;
    }
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        $record = $this->entityManager->getRepository(OrganizationMembershipRecord::class)->findOneBy([
            'organizationId' => $organizationId->toString(), 'id' => $membershipId->toString(),
        ]);
        return $record instanceof OrganizationMembershipRecord
            ? $this->toAggregate($record)
            : throw OrganizationMembershipNotFound::withId($membershipId);
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleCode $roleCode): int
    {
        $rows = $this->entityManager->getConnection()->fetchFirstColumn(
            <<<'SQL'
                SELECT id
                FROM identity_access.organization_memberships
                WHERE organization_id = :organization_id
                  AND status = 'ACTIVE'
                  AND role_assignments::jsonb @> CAST(:assignment AS JSONB)
                FOR UPDATE
                SQL,
            [
                'organization_id' => $organizationId->toString(),
                'assignment' => json_encode([['roleCode' => $roleCode->value()]], JSON_THROW_ON_ERROR),
            ],
        );

        return count($rows);
    }
    private function toAggregate(OrganizationMembershipRecord $record): OrganizationMembership
    {
        $assignments = array_map(fn(array $assignment): IntendedRoleAssignment => IntendedRoleAssignment::forRole(
            $assignment['roleCode'],
            array_map(fn(string $id): StoreId => StoreId::fromString($id, $this->uuidFactory), $assignment['storeIds']),
        ), $record->roleAssignments());
        return OrganizationMembership::reconstitute(
            OrganizationMembershipId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            UserId::fromString($record->userId(), $this->uuidFactory),
            MembershipStatus::from($record->status()),
            $assignments,
            $record->authorizationVersion(),
            ActorId::fromString($record->createdBy(), $this->uuidFactory),
            $record->createdAt(),
            ActorId::fromString($record->updatedBy(), $this->uuidFactory),
            $record->updatedAt(),
            null !== $record->suspendedBy() ? ActorId::fromString($record->suspendedBy(), $this->uuidFactory) : null,
            $record->suspendedAt(),
            null !== $record->revokedBy() ? ActorId::fromString($record->revokedBy(), $this->uuidFactory) : null,
            $record->revokedAt(),
            $record->version(),
        );
    }
}
