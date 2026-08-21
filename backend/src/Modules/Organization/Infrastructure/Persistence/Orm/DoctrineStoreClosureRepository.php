<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosure;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureNotFound;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureRepository;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureStatus;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreClosureId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineStoreClosureRepository implements StoreClosureRepository
{
    public function __construct(private EntityManagerInterface $entityManager, private UuidFactory $uuidFactory) {}

    public function save(StoreClosure $closure): void
    {
        $record = $this->entityManager->find(StoreClosureRecord::class, $closure->id()->toString());
        if ($record instanceof StoreClosureRecord) {
            $record->synchronize($closure);
        } else {
            $this->entityManager->persist(StoreClosureRecord::fromAggregate($closure));
        }
        $this->entityManager->flush();
    }

    public function getActiveForStore(OrganizationId $organizationId, StoreId $storeId): StoreClosure
    {
        $record = $this->entityManager->createQueryBuilder()
            ->select('closure')
            ->from(StoreClosureRecord::class, 'closure')
            ->where('closure.organizationId = :organizationId')
            ->andWhere('closure.storeId = :storeId')
            ->andWhere('closure.status IN (:statuses)')
            ->setParameter('organizationId', $organizationId->toString())
            ->setParameter('storeId', $storeId->toString())
            ->setParameter('statuses', [StoreClosureStatus::Requested->value, StoreClosureStatus::InProgress->value, StoreClosureStatus::Ready->value])
            ->getQuery()->getOneOrNullResult();

        if (!$record instanceof StoreClosureRecord) {
            throw StoreClosureNotFound::activeForStore($storeId);
        }

        return StoreClosure::reconstitute(
            StoreClosureId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            StoreId::fromString($record->storeId(), $this->uuidFactory),
            StoreClosureStatus::from($record->status()),
            $record->reason(),
            $record->blockers(),
            ActorId::fromString($record->requestedBy(), $this->uuidFactory),
            $record->requestedAt(),
            null !== $record->completedBy() ? ActorId::fromString($record->completedBy(), $this->uuidFactory) : null,
            $record->completedAt(),
            $record->version(),
        );
    }
}
