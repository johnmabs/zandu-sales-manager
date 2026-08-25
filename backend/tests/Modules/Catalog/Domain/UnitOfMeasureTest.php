<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureActivated;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureCreated;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureDeactivated;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureUpdated;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCode;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureDimension;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureName;
use Zandu\Modules\Catalog\Domain\UnitOfMeasurePrecision;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureStatus;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final class UnitOfMeasureTest extends TestCase
{
    private const UNIT_ID = '0198d22a-e5fc-7416-85e6-2e65291fa9b4';
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    public function testValueObjectsNormalizeTheirValues(): void
    {
        self::assertSame('KG', UnitOfMeasureCode::fromString(' kg ')->value());
        self::assertSame('Kilogramme net', UnitOfMeasureName::fromString(' Kilogramme   net ')->value());
        self::assertSame(12, UnitOfMeasurePrecision::fromInt(12)->value());
    }

    #[DataProvider('invalidValues')]
    public function testValueObjectsRejectInvalidValues(callable $operation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $operation();
    }

    /** @return iterable<string, array{callable(): object}> */
    public static function invalidValues(): iterable
    {
        yield 'empty code' => [static fn(): UnitOfMeasureCode => UnitOfMeasureCode::fromString('')];
        yield 'invalid code' => [static fn(): UnitOfMeasureCode => UnitOfMeasureCode::fromString('kg!')];
        yield 'empty name' => [static fn(): UnitOfMeasureName => UnitOfMeasureName::fromString('  ')];
        yield 'negative precision' => [static fn(): UnitOfMeasurePrecision => UnitOfMeasurePrecision::fromInt(-1)];
        yield 'excessive precision' => [static fn(): UnitOfMeasurePrecision => UnitOfMeasurePrecision::fromInt(13)];
    }

    public function testCreationIsTenantOwnedActiveAndRecordsAnUtcEvent(): void
    {
        $unit = $this->unit();
        $events = $unit->releaseEvents();

        self::assertSame(self::ORGANIZATION_ID, $unit->organizationId()->toString());
        self::assertSame(UnitOfMeasureStatus::Active, $unit->status());
        self::assertSame(1, $unit->version());
        self::assertCount(1, $events);
        self::assertInstanceOf(UnitOfMeasureCreated::class, $events[0]);
        self::assertSame('UTC', $events[0]->occurredAt()->getTimezone()->getName());
    }

    public function testUpdateRequiresAnActiveUnitAndAnExplicitRoundingMode(): void
    {
        $unit = $this->unit();
        $unit->releaseEvents();

        $unit->update(
            UnitOfMeasureName::fromString('Grammes'),
            UnitOfMeasureDimension::Mass,
            UnitOfMeasurePrecision::fromInt(3),
            RoundingMode::HalfEven,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T11:00:00+01:00'),
        );

        self::assertSame('Grammes', $unit->name()->value());
        self::assertSame(3, $unit->precision()->value());
        self::assertSame(RoundingMode::HalfEven, $unit->roundingMode());
        self::assertSame(2, $unit->version());
        self::assertInstanceOf(UnitOfMeasureUpdated::class, $unit->releaseEvents()[0]);
    }

    public function testDeactivationPreventsSelectionUntilReactivation(): void
    {
        $unit = $this->unit();
        $unit->releaseEvents();
        $at = new DateTimeImmutable('2026-08-25T12:00:00+01:00');

        $unit->deactivate($this->actorId(), $at);
        self::assertSame(UnitOfMeasureStatus::Inactive, $unit->status());
        self::assertInstanceOf(UnitOfMeasureDeactivated::class, $unit->releaseEvents()[0]);

        try {
            $unit->ensureSelectable();
            self::fail('An inactive unit must not be selectable.');
        } catch (LogicException $exception) {
            self::assertSame('An inactive unit of measure cannot be selected.', $exception->getMessage());
        }

        $unit->activate($this->actorId(), $at);
        $unit->ensureSelectable();
        self::assertSame(UnitOfMeasureStatus::Active, $unit->status());
        self::assertSame(3, $unit->version());
        self::assertInstanceOf(UnitOfMeasureActivated::class, $unit->releaseEvents()[0]);
    }

    public function testInactiveUnitCannotBeUpdatedOrDeactivatedAgain(): void
    {
        $unit = $this->unit();
        $unit->deactivate($this->actorId(), new DateTimeImmutable('2026-08-25T12:00:00Z'));

        $this->expectException(LogicException::class);
        $unit->deactivate($this->actorId(), new DateTimeImmutable('2026-08-25T13:00:00Z'));
    }

    public function testReconstitutionRejectsANonPositiveVersion(): void
    {
        $factory = new SymfonyUuidFactory();

        $this->expectException(InvalidArgumentException::class);
        UnitOfMeasure::reconstitute(
            UnitOfMeasureId::fromString(self::UNIT_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            UnitOfMeasureCode::fromString('KG'),
            UnitOfMeasureName::fromString('Kilogramme'),
            UnitOfMeasureDimension::Mass,
            UnitOfMeasurePrecision::fromInt(3),
            RoundingMode::HalfUp,
            UnitOfMeasureStatus::Active,
            0,
        );
    }

    private function unit(): UnitOfMeasure
    {
        $factory = new SymfonyUuidFactory();

        return UnitOfMeasure::create(
            UnitOfMeasureId::fromString(self::UNIT_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            UnitOfMeasureCode::fromString('kg'),
            UnitOfMeasureName::fromString('Kilogramme'),
            UnitOfMeasureDimension::Mass,
            UnitOfMeasurePrecision::fromInt(3),
            RoundingMode::HalfUp,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T09:00:00+01:00'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
