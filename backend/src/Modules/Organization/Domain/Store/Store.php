<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\Store;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Event\StoreClosureCancelled;
use Zandu\Modules\Organization\Domain\Store\Event\StoreClosureRequested;
use Zandu\Modules\Organization\Domain\Store\Event\StoreCreated;
use Zandu\Modules\Organization\Domain\Store\Event\StoreEvent;
use Zandu\Modules\Organization\Domain\Store\Event\StoreReactivated;
use Zandu\Modules\Organization\Domain\Store\Event\StoreSuspended;
use Zandu\Modules\Organization\Domain\Store\Event\StoreUpdated;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class Store implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<StoreEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly StoreId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreCode $code,
        private StoreName $name,
        private StoreStatus $status,
        private ?StoreAddress $address,
        private TimeZone $timeZone,
        private Currency $currency,
        private Locale $locale,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ActorId $updatedBy,
        private DateTimeImmutable $updatedAt,
        private ?ActorId $suspendedBy,
        private ?DateTimeImmutable $suspendedAt,
        private ?StoreStatus $statusBeforeClosure,
        private ?ActorId $closureRequestedBy,
        private ?DateTimeImmutable $closureRequestedAt,
        private ?ActorId $closedBy,
        private ?DateTimeImmutable $closedAt,
        private int $version,
    ) {
        $this->assertValidVersion();
    }

    public static function create(
        StoreId $id,
        OrganizationId $organizationId,
        StoreCode $code,
        StoreName $name,
        ?StoreAddress $address,
        TimeZone $timeZone,
        Currency $currency,
        Locale $locale,
        Currency $organizationCurrency,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        if (!$currency->equals($organizationCurrency)) {
            throw new LogicException('Store currency must match the organization default currency.');
        }

        $occurredAt = self::utc($occurredAt);
        $store = new self(
            $id,
            $organizationId,
            $code,
            $name,
            StoreStatus::Active,
            $address,
            $timeZone,
            $currency,
            $locale,
            $actorId,
            $occurredAt,
            $actorId,
            $occurredAt,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            1,
        );
        $store->recordedEvents[] = new StoreCreated($organizationId, $id, $actorId, $occurredAt);

        return $store;
    }

    public static function reconstitute(
        StoreId $id,
        OrganizationId $organizationId,
        StoreCode $code,
        StoreName $name,
        StoreStatus $status,
        ?StoreAddress $address,
        TimeZone $timeZone,
        Currency $currency,
        Locale $locale,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
        ActorId $updatedBy,
        DateTimeImmutable $updatedAt,
        ?ActorId $suspendedBy,
        ?DateTimeImmutable $suspendedAt,
        ?StoreStatus $statusBeforeClosure,
        ?ActorId $closureRequestedBy,
        ?DateTimeImmutable $closureRequestedAt,
        ?ActorId $closedBy,
        ?DateTimeImmutable $closedAt,
        int $version,
    ): self {
        return new self(
            $id,
            $organizationId,
            $code,
            $name,
            $status,
            $address,
            $timeZone,
            $currency,
            $locale,
            $createdBy,
            $createdAt,
            $updatedBy,
            $updatedAt,
            $suspendedBy,
            $suspendedAt,
            $statusBeforeClosure,
            $closureRequestedBy,
            $closureRequestedAt,
            $closedBy,
            $closedAt,
            $version,
        );
    }

    public function update(
        StoreName $name,
        ?StoreAddress $address,
        TimeZone $timeZone,
        Locale $locale,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireStatus(StoreStatus::Active, 'Only an active store can be updated.');
        $this->name = $name;
        $this->address = $address;
        $this->timeZone = $timeZone;
        $this->locale = $locale;
        $occurredAt = self::utc($occurredAt);
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new StoreUpdated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function suspend(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(StoreStatus::Active, 'Only an active store can be suspended.');
        $occurredAt = self::utc($occurredAt);
        $this->status = StoreStatus::Suspended;
        $this->suspendedBy = $actorId;
        $this->suspendedAt = $occurredAt;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new StoreSuspended($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function reactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(StoreStatus::Suspended, 'Only a suspended store can be reactivated.');
        $occurredAt = self::utc($occurredAt);
        $this->status = StoreStatus::Active;
        $this->suspendedBy = null;
        $this->suspendedAt = null;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new StoreReactivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function requestClosure(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (!in_array($this->status, [StoreStatus::Active, StoreStatus::Suspended], true)) {
            throw new LogicException('Only an active or suspended store can request closure.');
        }

        $occurredAt = self::utc($occurredAt);
        $this->statusBeforeClosure = $this->status;
        $this->status = StoreStatus::ClosurePending;
        $this->closureRequestedBy = $actorId;
        $this->closureRequestedAt = $occurredAt;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new StoreClosureRequested($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function cancelClosure(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(StoreStatus::ClosurePending, 'Only a store pending closure can cancel closure.');
        $occurredAt = self::utc($occurredAt);
        $this->status = $this->statusBeforeClosure ?? StoreStatus::Active;
        $this->statusBeforeClosure = null;
        $this->closureRequestedBy = null;
        $this->closureRequestedAt = null;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new StoreClosureCancelled($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    /** @return list<StoreEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }

    public function id(): StoreId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function code(): StoreCode
    {
        return $this->code;
    }
    public function name(): StoreName
    {
        return $this->name;
    }
    public function status(): StoreStatus
    {
        return $this->status;
    }
    public function address(): ?StoreAddress
    {
        return $this->address;
    }
    public function timeZone(): TimeZone
    {
        return $this->timeZone;
    }
    public function currency(): Currency
    {
        return $this->currency;
    }
    public function locale(): Locale
    {
        return $this->locale;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function updatedBy(): ActorId
    {
        return $this->updatedBy;
    }
    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function suspendedBy(): ?ActorId
    {
        return $this->suspendedBy;
    }
    public function suspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }
    public function statusBeforeClosure(): ?StoreStatus
    {
        return $this->statusBeforeClosure;
    }
    public function closureRequestedBy(): ?ActorId
    {
        return $this->closureRequestedBy;
    }
    public function closureRequestedAt(): ?DateTimeImmutable
    {
        return $this->closureRequestedAt;
    }
    public function closedBy(): ?ActorId
    {
        return $this->closedBy;
    }
    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    private function changedBy(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->updatedBy = $actorId;
        $this->updatedAt = $occurredAt;
        $this->advanceVersion();
    }

    private function requireStatus(StoreStatus $status, string $message): void
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
