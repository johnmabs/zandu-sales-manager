<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
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
        if (!$record instanceof OrganizationMembershipRecord) {
            return null;
        }
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
            $record->version(),
        );
    }
}
