<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierName;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierNotFound;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierStatus;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineSupplierRepository implements SupplierRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UuidFactory $uuidFactory,
    ) {}

    public function save(Supplier $supplier): void
    {
        $record = $this->entityManager->find(SupplierRecord::class, $supplier->id()->toString());
        if ($record instanceof SupplierRecord) {
            $expectedVersion = $supplier->version() - 1;
            if ($record->version() !== $expectedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expectedVersion, $record->version());
            }
            $record->synchronize($supplier);
        } else {
            $this->entityManager->persist(SupplierRecord::fromAggregate($supplier));
        }
        $this->entityManager->flush();
    }

    public function get(OrganizationId $organizationId, SupplierId $supplierId): Supplier
    {
        return $this->find($organizationId, $supplierId) ?? throw SupplierNotFound::withId($supplierId);
    }

    public function find(OrganizationId $organizationId, SupplierId $supplierId): ?Supplier
    {
        $record = $this->entityManager->getRepository(SupplierRecord::class)->findOneBy([
            'id' => $supplierId->toString(),
            'organizationId' => $organizationId->toString(),
        ]);

        return $record instanceof SupplierRecord ? $this->toAggregate($record) : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        $records = $this->entityManager->getRepository(SupplierRecord::class)->findBy(
            ['organizationId' => $organizationId->toString()],
            ['name' => 'ASC', 'id' => 'ASC'],
        );

        return array_map($this->toAggregate(...), $records);
    }

    private function toAggregate(SupplierRecord $record): Supplier
    {
        return Supplier::reconstitute(
            SupplierId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            SupplierName::fromString($record->name()),
            $record->phone(),
            $record->email(),
            $record->address(),
            $record->notes(),
            SupplierStatus::from($record->status()),
            $record->createdAt(),
            ActorId::fromString($record->createdBy(), $this->uuidFactory),
            $record->updatedAt(),
            null !== $record->updatedBy() ? ActorId::fromString($record->updatedBy(), $this->uuidFactory) : null,
            $record->version(),
        );
    }
}
