<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationStatus;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationNotFound;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineOrganizationInvitationRepository implements OrganizationInvitationRepository
{
    public function __construct(private EntityManagerInterface $entityManager, private UuidFactory $uuidFactory) {}
    public function save(OrganizationInvitation $invitation): void
    {
        $record = $this->entityManager->find(OrganizationInvitationRecord::class, $invitation->id()->toString());
        if ($record instanceof OrganizationInvitationRecord) {
            $expectedVersion = $invitation->version() - 1;
            if ($record->version() !== $expectedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expectedVersion, $record->version());
            }
            $record->synchronize($invitation);
        } else {
            $this->entityManager->persist(OrganizationInvitationRecord::fromAggregate($invitation));
        }
        $this->entityManager->flush();
    }
    public function get(OrganizationId $organizationId, OrganizationInvitationId $invitationId): OrganizationInvitation
    {
        $record = $this->entityManager->getRepository(OrganizationInvitationRecord::class)->findOneBy(['organizationId' => $organizationId->toString(), 'id' => $invitationId->toString()]);
        return $record instanceof OrganizationInvitationRecord ? $this->toAggregate($record) : throw OrganizationInvitationNotFound::forToken();
    }
    public function getByTokenHash(OrganizationId $organizationId, string $tokenHash): OrganizationInvitation
    {
        $record = $this->entityManager->getRepository(OrganizationInvitationRecord::class)->findOneBy(['organizationId' => $organizationId->toString(), 'tokenHash' => $tokenHash]);
        return $record instanceof OrganizationInvitationRecord ? $this->toAggregate($record) : throw OrganizationInvitationNotFound::forToken();
    }
    public function pendingExists(OrganizationId $organizationId, InvitationEmail $email, DateTimeImmutable $now): bool
    {
        return null !== $this->entityManager->createQueryBuilder()->select('1')->from(OrganizationInvitationRecord::class, 'i')
            ->where('i.organizationId = :organizationId')->andWhere('i.email = :email')->andWhere('i.status = :status')->andWhere('i.expiresAt > :now')
            ->setParameter('organizationId', $organizationId->toString())
            ->setParameter('email', $email->value())
            ->setParameter('status', InvitationStatus::Pending->value)
            ->setParameter('now', $now)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
    public function findExpiredPending(OrganizationId $organizationId, DateTimeImmutable $now, int $limit): array
    {
        $records = $this->entityManager->getRepository(OrganizationInvitationRecord::class)->createQueryBuilder('i')
            ->where('i.organizationId = :organizationId')->andWhere('i.status = :status')->andWhere('i.expiresAt <= :now')
            ->setParameter('organizationId', $organizationId->toString())
            ->setParameter('status', InvitationStatus::Pending->value)
            ->setParameter('now', $now)
            ->setMaxResults($limit)->getQuery()->getResult();
        return array_values(array_map(fn(OrganizationInvitationRecord $record): OrganizationInvitation => $this->toAggregate($record), $records));
    }
    private function toAggregate(OrganizationInvitationRecord $record): OrganizationInvitation
    {
        $assignments = array_map(fn(array $assignment): IntendedRoleAssignment => IntendedRoleAssignment::forRole(
            $assignment['roleCode'],
            array_map(fn(string $id): StoreId => StoreId::fromString($id, $this->uuidFactory), $assignment['storeIds']),
        ), $record->intendedRoleAssignments());
        return OrganizationInvitation::reconstitute(
            OrganizationInvitationId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            InvitationEmail::fromString($record->email()),
            ActorId::fromString($record->invitedBy(), $this->uuidFactory),
            $record->tokenHash(),
            $record->expiresAt(),
            InvitationStatus::from($record->status()),
            $assignments,
            null !== $record->acceptedBy() ? UserId::fromString($record->acceptedBy(), $this->uuidFactory) : null,
            $record->acceptedAt(),
            $record->version(),
        );
    }
}
