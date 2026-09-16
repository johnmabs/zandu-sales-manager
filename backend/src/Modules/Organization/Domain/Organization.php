<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

use DateTimeImmutable;
use LogicException;
use Zandu\Modules\Organization\Domain\Event\OrganizationClosed;
use Zandu\Modules\Organization\Domain\Event\OrganizationClosureRequested;
use Zandu\Modules\Organization\Domain\Event\OrganizationCreated;
use Zandu\Modules\Organization\Domain\Event\OrganizationEvent;
use Zandu\Modules\Organization\Domain\Event\OrganizationReactivated;
use Zandu\Modules\Organization\Domain\Event\OrganizationSuspended;
use Zandu\Modules\Organization\Domain\Event\OrganizationUpdated;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class Organization implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<OrganizationEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly OrganizationId $id,
        private OrganizationName $name,
        private OrganizationStatus $status,
        private CountryCode $countryCode,
        private Currency $defaultCurrency,
        private TimeZone $defaultTimeZone,
        private Locale $defaultLocale,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ActorId $updatedBy,
        private DateTimeImmutable $updatedAt,
        private ?ActorId $suspendedBy,
        private ?DateTimeImmutable $suspendedAt,
        private ?ActorId $closureRequestedBy,
        private ?DateTimeImmutable $closureRequestedAt,
        private ?ActorId $closedBy,
        private ?DateTimeImmutable $closedAt,
        private int $version,
    ) {
        $this->assertValidVersion();
    }

    public static function create(
        OrganizationId $id,
        OrganizationName $name,
        CountryCode $countryCode,
        Currency $defaultCurrency,
        TimeZone $defaultTimeZone,
        Locale $defaultLocale,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        $organization = new self(
            $id,
            $name,
            OrganizationStatus::Active,
            $countryCode,
            $defaultCurrency,
            $defaultTimeZone,
            $defaultLocale,
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
            1,
        );
        $organization->recordedEvents[] = new OrganizationCreated($id, $actorId, $occurredAt);

        return $organization;
    }

    public static function reconstitute(
        OrganizationId $id,
        OrganizationName $name,
        OrganizationStatus $status,
        CountryCode $countryCode,
        Currency $defaultCurrency,
        TimeZone $defaultTimeZone,
        Locale $defaultLocale,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
        ActorId $updatedBy,
        DateTimeImmutable $updatedAt,
        ?ActorId $suspendedBy,
        ?DateTimeImmutable $suspendedAt,
        ?ActorId $closureRequestedBy,
        ?DateTimeImmutable $closureRequestedAt,
        ?ActorId $closedBy,
        ?DateTimeImmutable $closedAt,
        int $version,
    ): self {
        return new self(
            $id,
            $name,
            $status,
            $countryCode,
            $defaultCurrency,
            $defaultTimeZone,
            $defaultLocale,
            $createdBy,
            $createdAt,
            $updatedBy,
            $updatedAt,
            $suspendedBy,
            $suspendedAt,
            $closureRequestedBy,
            $closureRequestedAt,
            $closedBy,
            $closedAt,
            $version,
        );
    }

    public function updateProfile(
        OrganizationName $name,
        CountryCode $countryCode,
        Currency $defaultCurrency,
        TimeZone $defaultTimeZone,
        Locale $defaultLocale,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireStatus(OrganizationStatus::Active, 'Only an active organization can be updated.');
        $this->name = $name;
        $this->countryCode = $countryCode;
        $this->defaultCurrency = $defaultCurrency;
        $this->defaultTimeZone = $defaultTimeZone;
        $this->defaultLocale = $defaultLocale;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new OrganizationUpdated($this->id, $actorId, $occurredAt);
    }

    public function suspend(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(OrganizationStatus::Active, 'Only an active organization can be suspended.');
        $this->status = OrganizationStatus::Suspended;
        $this->suspendedBy = $actorId;
        $this->suspendedAt = $occurredAt;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new OrganizationSuspended($this->id, $actorId, $occurredAt);
    }

    public function reactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(OrganizationStatus::Suspended, 'Only a suspended organization can be reactivated.');
        $this->status = OrganizationStatus::Active;
        $this->suspendedBy = null;
        $this->suspendedAt = null;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new OrganizationReactivated($this->id, $actorId, $occurredAt);
    }

    public function requestClosure(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (!in_array($this->status, [OrganizationStatus::Active, OrganizationStatus::Suspended], true)) {
            throw new LogicException('Only an active or suspended organization can request closure.');
        }

        $this->status = OrganizationStatus::ClosurePending;
        $this->closureRequestedBy = $actorId;
        $this->closureRequestedAt = $occurredAt;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new OrganizationClosureRequested($this->id, $actorId, $occurredAt);
    }

    public function close(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(OrganizationStatus::ClosurePending, 'Only an organization pending closure can be closed.');
        $this->status = OrganizationStatus::Closed;
        $this->closedBy = $actorId;
        $this->closedAt = $occurredAt;
        $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new OrganizationClosed($this->id, $actorId, $occurredAt);
    }

    /** @return list<OrganizationEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }
    public function name(): OrganizationName
    {
        return $this->name;
    }
    public function status(): OrganizationStatus
    {
        return $this->status;
    }
    public function countryCode(): CountryCode
    {
        return $this->countryCode;
    }
    public function defaultCurrency(): Currency
    {
        return $this->defaultCurrency;
    }
    public function defaultTimeZone(): TimeZone
    {
        return $this->defaultTimeZone;
    }
    public function defaultLocale(): Locale
    {
        return $this->defaultLocale;
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

    private function requireStatus(OrganizationStatus $status, string $message): void
    {
        if ($this->status !== $status) {
            throw new LogicException($message);
        }
    }
}
