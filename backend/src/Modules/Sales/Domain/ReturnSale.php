<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ReturnSaleId, ReturnSaleLineId, SaleId, StoreId};
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class ReturnSale implements VersionedAggregate
{
    use TracksAggregateVersion;

    private function __construct(
        private readonly ReturnSaleId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private readonly SaleId $saleId,
        private ReturnSaleStatus $status,
        private readonly ?string $reason,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ?string $businessDate = null,
        private ?ActorId $completedBy = null,
        private ?DateTimeImmutable $completedAt = null,
        private ?ActorId $cancelledBy = null,
        private ?DateTimeImmutable $cancelledAt = null,
        private int $version = 1,
        /** @var list<ReturnSaleLine> */
        private array $lines = [],
    ) {
        $this->assertValidVersion();
    }

    public static function create(
        ReturnSaleId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        SaleId $saleId,
        ?string $reason,
        ActorContext $actor,
        DateTimeImmutable $at,
    ): self {
        self::assertActorOrganization($actor, $organizationId);

        return new self(
            $id,
            $organizationId,
            $storeId,
            $saleId,
            ReturnSaleStatus::Draft,
            self::normalizeReason($reason),
            $actor->actorId(),
            self::utc($at),
        );
    }

    /** @param list<ReturnSaleLine> $lines */
    public static function reconstitute(
        ReturnSaleId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        SaleId $saleId,
        ReturnSaleStatus $status,
        ?string $reason,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
        ?string $businessDate,
        ?ActorId $completedBy,
        ?DateTimeImmutable $completedAt,
        ?ActorId $cancelledBy,
        ?DateTimeImmutable $cancelledAt,
        int $version,
        array $lines,
    ): self {
        return new self($id, $organizationId, $storeId, $saleId, $status, $reason, $createdBy, $createdAt, $businessDate, $completedBy, $completedAt, $cancelledBy, $cancelledAt, $version, $lines);
    }

    public function addLine(ReturnSaleLine $line): void
    {
        $this->ensureEditable();
        if (!$line->originalLine()->saleId()->equals($this->saleId)) {
            throw new LogicException('Return line belongs to another sale.');
        }
        if (null !== $line->originalCostSnapshot() && !$line->originalCostSnapshot()->organizationId()->equals($this->organizationId)) {
            throw new LogicException('Return line cost snapshot belongs to another organization.');
        }
        foreach ($this->lines as $existing) {
            if ($existing->saleLineId()->equals($line->saleLineId())) {
                throw SalesRuleViolation::with('RETURN_LINE_ALREADY_EXISTS', 'The sale line is already present in this return.');
            }
        }

        $this->lines[] = $line;
        $this->advanceVersion();
    }

    /** @param array<string, ReturnAmounts> $lineAmounts indexed by return sale line id */
    public function complete(ActorContext $actor, DateTimeImmutable $at, string $businessDate, array $lineAmounts): void
    {
        $this->ensureEditable();
        if ([] === $this->lines) {
            throw SalesRuleViolation::with('RETURN_EMPTY', 'A return must contain at least one line.');
        }
        self::assertActorOrganization($actor, $this->organizationId);
        self::assertBusinessDate($businessDate);

        $allocatedLines = [];
        foreach ($this->lines as $line) {
            $key = $line->id()->toString();
            $amounts = $lineAmounts[$key] ?? throw SalesRuleViolation::with(
                'RETURN_AMOUNTS_REQUIRED',
                'Every return line must have calculated amounts before completion.',
            );
            $allocatedLines[] = $line->withAmounts($amounts);
            unset($lineAmounts[$key]);
        }
        if ([] !== $lineAmounts) {
            throw new LogicException('Return amounts contain an unknown return line.');
        }

        $this->lines = $allocatedLines;
        $this->status = ReturnSaleStatus::Completed;
        $this->businessDate = $businessDate;
        $this->completedBy = $actor->actorId();
        $this->completedAt = self::utc($at);
        $this->advanceVersion();
    }

    public function cancel(ActorContext $actor, DateTimeImmutable $at): void
    {
        $this->ensureEditable();
        self::assertActorOrganization($actor, $this->organizationId);

        $this->status = ReturnSaleStatus::Cancelled;
        $this->cancelledBy = $actor->actorId();
        $this->cancelledAt = self::utc($at);
        $this->advanceVersion();
    }

    public function line(ReturnSaleLineId $lineId): ReturnSaleLine
    {
        foreach ($this->lines as $line) {
            if ($line->id()->equals($lineId)) {
                return $line;
            }
        }

        throw SalesRuleViolation::with('RETURN_LINE_NOT_FOUND', 'Return line not found.');
    }

    public function id(): ReturnSaleId
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

    public function saleId(): SaleId
    {
        return $this->saleId;
    }

    public function status(): ReturnSaleStatus
    {
        return $this->status;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function businessDate(): ?string
    {
        return $this->businessDate;
    }

    public function completedBy(): ?ActorId
    {
        return $this->completedBy;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function cancelledBy(): ?ActorId
    {
        return $this->cancelledBy;
    }

    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }

    /** @return list<ReturnSaleLine> */
    public function lines(): array
    {
        return $this->lines;
    }

    private function ensureEditable(): void
    {
        if (ReturnSaleStatus::Draft !== $this->status) {
            throw SalesRuleViolation::with(
                ReturnSaleStatus::Completed === $this->status ? 'RETURN_ALREADY_COMPLETED' : 'RETURN_NOT_EDITABLE',
                'The return is not editable.',
            );
        }
    }

    private static function assertActorOrganization(ActorContext $actor, OrganizationId $organizationId): void
    {
        if (!$actor->organizationId()->equals($organizationId)) {
            throw new LogicException('Actor organization does not match return organization.');
        }
    }

    private static function assertBusinessDate(string $businessDate): void
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $businessDate);
        if (false === $parsed || $parsed->format('Y-m-d') !== $businessDate) {
            throw new LogicException('Business date must use the Y-m-d format.');
        }
    }

    private static function normalizeReason(?string $reason): ?string
    {
        if (null === $reason) {
            return null;
        }

        $reason = trim($reason);
        if ('' === $reason) {
            return null;
        }
        if (mb_strlen($reason) > 255) {
            throw new LogicException('Return reason cannot exceed 255 characters.');
        }

        return $reason;
    }

    private static function utc(DateTimeImmutable $at): DateTimeImmutable
    {
        return $at->setTimezone(new DateTimeZone('UTC'));
    }
}
