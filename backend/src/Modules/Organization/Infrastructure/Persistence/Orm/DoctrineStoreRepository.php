<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\Store\StoreNotFound;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\Store\StoreStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Money\Currency;

final readonly class DoctrineStoreRepository implements StoreRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UuidFactory $uuidFactory,
    ) {}

    public function save(Store $store): void
    {
        $record = $this->entityManager->find(StoreRecord::class, $store->id()->toString());
        if ($record instanceof StoreRecord) {
            $expectedVersion = $store->version() - 1;
            if ($record->version() !== $expectedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expectedVersion, $record->version());
            }
            $record->synchronize($store);
        } else {
            $this->entityManager->persist(StoreRecord::fromAggregate($store));
        }
        $this->entityManager->flush();
    }

    public function get(OrganizationId $organizationId, StoreId $storeId): Store
    {
        return $this->find($organizationId, $storeId) ?? throw StoreNotFound::withId($storeId);
    }

    public function find(OrganizationId $organizationId, StoreId $storeId): ?Store
    {
        $record = $this->entityManager->getRepository(StoreRecord::class)->findOneBy([
            'id' => $storeId->toString(),
            'organizationId' => $organizationId->toString(),
        ]);

        return $record instanceof StoreRecord ? $this->toAggregate($record) : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        $records = $this->entityManager->getRepository(StoreRecord::class)->findBy(
            ['organizationId' => $organizationId->toString()],
            ['name' => 'ASC', 'id' => 'ASC'],
        );

        return array_map($this->toAggregate(...), $records);
    }

    public function codeExists(OrganizationId $organizationId, StoreCode $code): bool
    {
        return null !== $this->entityManager->getRepository(StoreRecord::class)->findOneBy([
            'organizationId' => $organizationId->toString(),
            'code' => $code->value(),
        ]);
    }

    private function toAggregate(StoreRecord $record): Store
    {
        return Store::reconstitute(
            StoreId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            StoreCode::fromString($record->code()),
            StoreName::fromString($record->name()),
            StoreStatus::from($record->status()),
            null !== $record->address() ? StoreAddress::fromString($record->address()) : null,
            TimeZone::fromString($record->timeZone()),
            Currency::fromCode($record->currency()),
            Locale::fromString($record->locale()),
            ActorId::fromString($record->createdBy(), $this->uuidFactory),
            $record->createdAt(),
            ActorId::fromString($record->updatedBy(), $this->uuidFactory),
            $record->updatedAt(),
            null !== $record->suspendedBy() ? ActorId::fromString($record->suspendedBy(), $this->uuidFactory) : null,
            $record->suspendedAt(),
            null !== $record->statusBeforeClosure() ? StoreStatus::from($record->statusBeforeClosure()) : null,
            null !== $record->closureRequestedBy() ? ActorId::fromString($record->closureRequestedBy(), $this->uuidFactory) : null,
            $record->closureRequestedAt(),
            null !== $record->closedBy() ? ActorId::fromString($record->closedBy(), $this->uuidFactory) : null,
            $record->closedAt(),
            $record->version(),
        );
    }
}
