<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use UnexpectedValueException;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCode;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureDimension;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureName;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureNotFound;
use Zandu\Modules\Catalog\Domain\UnitOfMeasurePrecision;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureStatus;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineUnitOfMeasureRepository implements UnitOfMeasureRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UuidFactory $uuidFactory,
    ) {}

    public function save(UnitOfMeasure $unit): void
    {
        $record = $this->entityManager->find(UnitOfMeasureRecord::class, $unit->id()->toString());

        if ($record instanceof UnitOfMeasureRecord) {
            $expectedPersistedVersion = $unit->version() - 1;
            if ($record->version() !== $expectedPersistedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch(
                    $record,
                    $expectedPersistedVersion,
                    $record->version(),
                );
            }
            $record->synchronize($unit);
        } else {
            $this->entityManager->persist(UnitOfMeasureRecord::fromAggregate($unit));
        }

        $this->entityManager->flush();
    }

    public function get(OrganizationId $organizationId, UnitOfMeasureId $unitId): UnitOfMeasure
    {
        return $this->find($organizationId, $unitId) ?? throw UnitOfMeasureNotFound::withId($unitId);
    }

    public function find(OrganizationId $organizationId, UnitOfMeasureId $unitId): ?UnitOfMeasure
    {
        $record = $this->entityManager->getRepository(UnitOfMeasureRecord::class)->findOneBy([
            'id' => $unitId->toString(),
            'organizationId' => $organizationId->toString(),
        ]);

        return $record instanceof UnitOfMeasureRecord ? $this->toAggregate($record) : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        $records = $this->entityManager->getRepository(UnitOfMeasureRecord::class)->findBy(
            ['organizationId' => $organizationId->toString()],
            ['name' => 'ASC', 'id' => 'ASC'],
        );

        return array_map($this->toAggregate(...), $records);
    }

    public function codeExists(OrganizationId $organizationId, UnitOfMeasureCode $code): bool
    {
        return null !== $this->entityManager->getRepository(UnitOfMeasureRecord::class)->findOneBy([
            'organizationId' => $organizationId->toString(),
            'code' => $code->value(),
        ]);
    }

    private function toAggregate(UnitOfMeasureRecord $record): UnitOfMeasure
    {
        return UnitOfMeasure::reconstitute(
            UnitOfMeasureId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            UnitOfMeasureCode::fromString($record->code()),
            UnitOfMeasureName::fromString($record->name()),
            UnitOfMeasureDimension::from($record->dimension()),
            UnitOfMeasurePrecision::fromInt($record->precision()),
            self::roundingMode($record->roundingMode()),
            UnitOfMeasureStatus::from($record->status()),
            $record->version(),
        );
    }

    private static function roundingMode(string $name): RoundingMode
    {
        return match ($name) {
            'Unnecessary' => RoundingMode::Unnecessary,
            'Up' => RoundingMode::Up,
            'Down' => RoundingMode::Down,
            'Ceiling' => RoundingMode::Ceiling,
            'Floor' => RoundingMode::Floor,
            'HalfUp' => RoundingMode::HalfUp,
            'HalfDown' => RoundingMode::HalfDown,
            'HalfEven' => RoundingMode::HalfEven,
            default => throw new UnexpectedValueException(sprintf('Unsupported persisted rounding mode "%s".', $name)),
        };
    }
}
