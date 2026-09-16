<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScopeType;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\ScopedStore;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipNotFound;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineOrganizationMembershipRepository implements OrganizationMembershipRepository
{
    public function __construct(private EntityManagerInterface $entityManager, private UuidFactory $uuidFactory) {}
    public function save(OrganizationMembership $membership): void
    {
        $record = $this->entityManager->find(OrganizationMembershipRecord::class, $membership->id()->toString());
        if ($record instanceof OrganizationMembershipRecord) {
            $expectedVersion = $membership->version() - 1;
            if ($record->version() !== $expectedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expectedVersion, $record->version());
            }
            $record->synchronize($membership);
        } else {
            $this->entityManager->persist(OrganizationMembershipRecord::fromAggregate($membership));
        }
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
    public function findAll(OrganizationId $organizationId): array
    {
        $records = $this->entityManager->getRepository(OrganizationMembershipRecord::class)->findBy(
            ['organizationId' => $organizationId->toString()],
            ['createdAt' => 'ASC', 'id' => 'ASC'],
        );
        return array_map($this->toAggregate(...), $records);
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
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
                'assignment' => json_encode([['roleId' => $roleId->toString()]], JSON_THROW_ON_ERROR),
            ],
        );

        return count($rows);
    }
    private function toAggregate(OrganizationMembershipRecord $record): OrganizationMembership
    {
        $organizationId = OrganizationId::fromString($record->organizationId(), $this->uuidFactory);
        $assignments = array_map(function (array $assignment) use ($organizationId): RoleAssignment {
            $storeIds = array_map(fn(string $id): StoreId => StoreId::fromString($id, $this->uuidFactory), $assignment['storeIds']);
            $scope = AccessScopeType::Organization->value === $assignment['scopeType']
                ? AccessScope::organization($organizationId)
                : AccessScope::selectedStores($organizationId, array_map(
                    static fn(StoreId $storeId): ScopedStore => new ScopedStore($storeId, $organizationId),
                    $storeIds,
                ));

            return RoleAssignment::assign(
                RoleId::fromString($assignment['roleId'], $this->uuidFactory),
                $scope,
                ActorId::fromString($assignment['assignedBy'], $this->uuidFactory),
                new DateTimeImmutable($assignment['assignedAt']),
                null !== $assignment['expiresAt'] ? new DateTimeImmutable($assignment['expiresAt']) : null,
            );
        }, $record->roleAssignments());
        return OrganizationMembership::reconstitute(
            OrganizationMembershipId::fromString($record->id(), $this->uuidFactory),
            $organizationId,
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
