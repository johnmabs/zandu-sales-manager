<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureActivated;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureCreated;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureDeactivated;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureEvent;
use Zandu\Modules\Catalog\Domain\Event\UnitOfMeasureUpdated;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class UnitOfMeasure implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<UnitOfMeasureEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly UnitOfMeasureId $id,
        private readonly OrganizationId $organizationId,
        private readonly UnitOfMeasureCode $code,
        private UnitOfMeasureName $name,
        private UnitOfMeasureDimension $dimension,
        private UnitOfMeasurePrecision $precision,
        private RoundingMode $roundingMode,
        private UnitOfMeasureStatus $status,
        private int $version,
    ) {
        $this->assertValidVersion();
    }

    public static function create(
        UnitOfMeasureId $id,
        OrganizationId $organizationId,
        UnitOfMeasureCode $code,
        UnitOfMeasureName $name,
        UnitOfMeasureDimension $dimension,
        UnitOfMeasurePrecision $precision,
        RoundingMode $roundingMode,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        $occurredAt = self::utc($occurredAt);
        $unit = new self(
            $id,
            $organizationId,
            $code,
            $name,
            $dimension,
            $precision,
            $roundingMode,
            UnitOfMeasureStatus::Active,
            1,
        );
        $unit->recordedEvents[] = new UnitOfMeasureCreated($organizationId, $id, $actorId, $occurredAt);

        return $unit;
    }

    public static function reconstitute(
        UnitOfMeasureId $id,
        OrganizationId $organizationId,
        UnitOfMeasureCode $code,
        UnitOfMeasureName $name,
        UnitOfMeasureDimension $dimension,
        UnitOfMeasurePrecision $precision,
        RoundingMode $roundingMode,
        UnitOfMeasureStatus $status,
        int $version,
    ): self {
        return new self($id, $organizationId, $code, $name, $dimension, $precision, $roundingMode, $status, $version);
    }

    public function update(
        UnitOfMeasureName $name,
        UnitOfMeasureDimension $dimension,
        UnitOfMeasurePrecision $precision,
        RoundingMode $roundingMode,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireStatus(UnitOfMeasureStatus::Active, 'Only an active unit of measure can be updated.');
        $this->name = $name;
        $this->dimension = $dimension;
        $this->precision = $precision;
        $this->roundingMode = $roundingMode;
        $this->advanceVersion();
        $this->recordedEvents[] = new UnitOfMeasureUpdated(
            $this->organizationId,
            $this->id,
            $actorId,
            self::utc($occurredAt),
        );
    }

    public function deactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(UnitOfMeasureStatus::Active, 'Only an active unit of measure can be deactivated.');
        $this->status = UnitOfMeasureStatus::Inactive;
        $this->advanceVersion();
        $this->recordedEvents[] = new UnitOfMeasureDeactivated(
            $this->organizationId,
            $this->id,
            $actorId,
            self::utc($occurredAt),
        );
    }

    public function activate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(UnitOfMeasureStatus::Inactive, 'Only an inactive unit of measure can be activated.');
        $this->status = UnitOfMeasureStatus::Active;
        $this->advanceVersion();
        $this->recordedEvents[] = new UnitOfMeasureActivated(
            $this->organizationId,
            $this->id,
            $actorId,
            self::utc($occurredAt),
        );
    }

    public function ensureSelectable(): void
    {
        $this->requireStatus(UnitOfMeasureStatus::Active, 'An inactive unit of measure cannot be selected.');
    }

    /** @return list<UnitOfMeasureEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function id(): UnitOfMeasureId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function code(): UnitOfMeasureCode
    {
        return $this->code;
    }
    public function name(): UnitOfMeasureName
    {
        return $this->name;
    }
    public function dimension(): UnitOfMeasureDimension
    {
        return $this->dimension;
    }
    public function precision(): UnitOfMeasurePrecision
    {
        return $this->precision;
    }
    public function roundingMode(): RoundingMode
    {
        return $this->roundingMode;
    }
    public function status(): UnitOfMeasureStatus
    {
        return $this->status;
    }

    private function requireStatus(UnitOfMeasureStatus $status, string $message): void
    {
        if ($this->status !== $status) {
            throw new LogicException($message);
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
