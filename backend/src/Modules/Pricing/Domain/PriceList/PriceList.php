<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListActivated;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListArchived;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListCreated;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListDeactivated;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListEvent;
use Zandu\Modules\Pricing\Domain\PriceList\Event\PriceListUpdated;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class PriceList implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<PriceListEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly PriceListId $id,
        private readonly OrganizationId $organizationId,
        private PriceListCode $code,
        private PriceListName $name,
        private Currency $currency,
        private PriceListStatus $status,
        private readonly PriceListScope $scope,
        private ?DateTimeImmutable $validFrom,
        private ?DateTimeImmutable $validTo,
        private PriceListPriority $priority,
        private readonly DateTimeImmutable $createdAt,
        private readonly ActorId $createdBy,
        private int $version,
    ) {
        $this->assertValidVersion();
        self::assertPeriod($validFrom, $validTo);
    }

    public static function createDraft(
        PriceListId $id,
        OrganizationId $organizationId,
        PriceListCode $code,
        PriceListName $name,
        Currency $currency,
        ?DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validTo,
        PriceListPriority $priority,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        $occurredAt = self::utc($occurredAt);
        $priceList = new self(
            $id,
            $organizationId,
            $code,
            $name,
            $currency,
            PriceListStatus::Draft,
            PriceListScope::Organization,
            null !== $validFrom ? self::utc($validFrom) : null,
            null !== $validTo ? self::utc($validTo) : null,
            $priority,
            $occurredAt,
            $actorId,
            1,
        );
        $priceList->recordedEvents[] = new PriceListCreated($organizationId, $id, $actorId, $occurredAt);

        return $priceList;
    }

    public static function reconstitute(
        PriceListId $id,
        OrganizationId $organizationId,
        PriceListCode $code,
        PriceListName $name,
        Currency $currency,
        PriceListStatus $status,
        PriceListScope $scope,
        ?DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validTo,
        PriceListPriority $priority,
        DateTimeImmutable $createdAt,
        ActorId $createdBy,
        int $version,
    ): self {
        return new self(
            $id,
            $organizationId,
            $code,
            $name,
            $currency,
            $status,
            $scope,
            null !== $validFrom ? self::utc($validFrom) : null,
            null !== $validTo ? self::utc($validTo) : null,
            $priority,
            self::utc($createdAt),
            $createdBy,
            $version,
        );
    }

    public function update(
        PriceListCode $code,
        PriceListName $name,
        Currency $currency,
        ?DateTimeImmutable $validFrom,
        ?DateTimeImmutable $validTo,
        PriceListPriority $priority,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireNotArchived('An archived price list cannot be updated.');
        if (!$this->currency->equals($currency)) {
            throw new LogicException('Price list currency is immutable.');
        }
        $validFrom = null !== $validFrom ? self::utc($validFrom) : null;
        $validTo = null !== $validTo ? self::utc($validTo) : null;
        self::assertPeriod($validFrom, $validTo);
        $this->code = $code;
        $this->name = $name;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->priority = $priority;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new PriceListUpdated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function activate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (!in_array($this->status, [PriceListStatus::Draft, PriceListStatus::Inactive], true)) {
            throw new LogicException('Only a draft or inactive price list can be activated.');
        }
        $this->status = PriceListStatus::Active;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new PriceListActivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function deactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (PriceListStatus::Active !== $this->status) {
            throw new LogicException('Only an active price list can be deactivated.');
        }
        $this->status = PriceListStatus::Inactive;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new PriceListDeactivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function archive(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireNotArchived('Price list is already archived.');
        $this->status = PriceListStatus::Archived;
        $occurredAt = $this->changedAt($occurredAt);
        $this->recordedEvents[] = new PriceListArchived($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function isSelectableAt(DateTimeImmutable $businessInstant): bool
    {
        $businessInstant = self::utc($businessInstant);

        return PriceListStatus::Active === $this->status
            && (null === $this->validFrom || $businessInstant >= $this->validFrom)
            && (null === $this->validTo || $businessInstant <= $this->validTo);
    }

    /** @return list<PriceListEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function id(): PriceListId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function code(): PriceListCode
    {
        return $this->code;
    }
    public function name(): PriceListName
    {
        return $this->name;
    }
    public function currency(): Currency
    {
        return $this->currency;
    }
    public function status(): PriceListStatus
    {
        return $this->status;
    }
    public function scope(): PriceListScope
    {
        return $this->scope;
    }
    public function validFrom(): ?DateTimeImmutable
    {
        return $this->validFrom;
    }
    public function validTo(): ?DateTimeImmutable
    {
        return $this->validTo;
    }
    public function priority(): PriceListPriority
    {
        return $this->priority;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }

    private static function assertPeriod(?DateTimeImmutable $validFrom, ?DateTimeImmutable $validTo): void
    {
        if (null !== $validFrom && null !== $validTo && $validTo < $validFrom) {
            throw new InvalidArgumentException('Price list validity end must not precede its start.');
        }
    }

    private function changedAt(DateTimeImmutable $occurredAt): DateTimeImmutable
    {
        $this->advanceVersion();

        return self::utc($occurredAt);
    }

    private function requireNotArchived(string $message): void
    {
        if (PriceListStatus::Archived === $this->status) {
            throw new LogicException($message);
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
