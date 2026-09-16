<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\StoreClosure;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreClosureId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class StoreClosure implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @param list<string> $blockers */
    private function __construct(
        private readonly StoreClosureId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private StoreClosureStatus $status,
        private readonly string $reason,
        private array $blockers,
        private readonly ActorId $requestedBy,
        private readonly DateTimeImmutable $requestedAt,
        private ?ActorId $completedBy,
        private ?DateTimeImmutable $completedAt,
        private int $version,
    ) {
        $this->assertValidVersion();
    }

    public static function request(
        StoreClosureId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        string $reason,
        ActorId $requestedBy,
        DateTimeImmutable $requestedAt,
    ): self {
        $reason = trim($reason);
        if ('' === $reason) {
            throw new InvalidArgumentException('A store closure reason is required.');
        }

        return new self(
            $id,
            $organizationId,
            $storeId,
            StoreClosureStatus::Requested,
            $reason,
            [],
            $requestedBy,
            self::utc($requestedAt),
            null,
            null,
            1,
        );
    }

    /** @param list<string> $blockers */
    public function evaluate(array $blockers): void
    {
        if (!in_array($this->status, [StoreClosureStatus::Requested, StoreClosureStatus::InProgress, StoreClosureStatus::Ready], true)) {
            throw new LogicException('A completed or cancelled store closure cannot be evaluated.');
        }

        $this->blockers = array_values(array_unique($blockers));
        $this->status = [] === $this->blockers ? StoreClosureStatus::Ready : StoreClosureStatus::InProgress;
        $this->advanceVersion();
    }

    public function cancel(): void
    {
        if (!in_array($this->status, [StoreClosureStatus::Requested, StoreClosureStatus::InProgress, StoreClosureStatus::Ready], true)) {
            throw new LogicException('Only an open store closure can be cancelled.');
        }
        $this->status = StoreClosureStatus::Cancelled;
        $this->advanceVersion();
    }

    public function complete(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if (StoreClosureStatus::Ready !== $this->status) {
            throw new LogicException('Only a ready store closure can be completed.');
        }
        $this->status = StoreClosureStatus::Completed;
        $this->completedBy = $actorId;
        $this->completedAt = self::utc($occurredAt);
        $this->advanceVersion();
    }

    /** @param list<string> $blockers */
    public static function reconstitute(
        StoreClosureId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        StoreClosureStatus $status,
        string $reason,
        array $blockers,
        ActorId $requestedBy,
        DateTimeImmutable $requestedAt,
        ?ActorId $completedBy,
        ?DateTimeImmutable $completedAt,
        int $version,
    ): self {
        return new self($id, $organizationId, $storeId, $status, $reason, $blockers, $requestedBy, $requestedAt, $completedBy, $completedAt, $version);
    }

    public function id(): StoreClosureId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function storeId(): StoreId
    {
        return $this->storeId;
    }
    public function status(): StoreClosureStatus
    {
        return $this->status;
    }
    public function reason(): string
    {
        return $this->reason;
    }
    /** @return list<string> */
    public function blockers(): array
    {
        return $this->blockers;
    }
    public function requestedBy(): ActorId
    {
        return $this->requestedBy;
    }
    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }
    public function completedBy(): ?ActorId
    {
        return $this->completedBy;
    }
    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
