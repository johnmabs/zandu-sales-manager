<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, StockCountId, StoreId};

final class StockCount
{
    private function __construct(
        private StockCountId $id,
        private OrganizationId $organizationId,
        private StoreId $storeId,
        private StockCountStatus $status,
        private StockCountMode $mode,
        private StockCountScopeType $scopeType,
        private int $totalLineCount,
        private int $countedLineCount,
        private int $reconciledLineCount,
        private ActorId $createdBy,
        private DateTimeImmutable $createdAt,
        private ?ActorId $startedBy,
        private ?DateTimeImmutable $startedAt,
        private ?ActorId $finalizationStartedBy,
        private ?DateTimeImmutable $finalizationStartedAt,
        private ?ActorId $completedBy,
        private ?DateTimeImmutable $completedAt,
        private ?ActorId $cancelledBy,
        private ?DateTimeImmutable $cancelledAt,
        private int $version,
    ) {}

    public static function create(StockCountId $id, OrganizationId $organizationId, StoreId $storeId, StockCountScopeType $scopeType, ActorId $createdBy, DateTimeImmutable $createdAt, StockCountMode $mode = StockCountMode::Blind): self
    {
        return new self($id, $organizationId, $storeId, StockCountStatus::Draft, $mode, $scopeType, 0, 0, 0, $createdBy, self::utc($createdAt), null, null, null, null, null, null, null, null, 1);
    }

    public static function reconstitute(StockCountId $id, OrganizationId $organizationId, StoreId $storeId, StockCountStatus $status, StockCountMode $mode, StockCountScopeType $scopeType, int $totalLineCount, int $countedLineCount, int $reconciledLineCount, ActorId $createdBy, DateTimeImmutable $createdAt, ?ActorId $startedBy, ?DateTimeImmutable $startedAt, ?ActorId $finalizationStartedBy, ?DateTimeImmutable $finalizationStartedAt, ?ActorId $completedBy, ?DateTimeImmutable $completedAt, ?ActorId $cancelledBy, ?DateTimeImmutable $cancelledAt, int $version): self
    {
        return new self($id, $organizationId, $storeId, $status, $mode, $scopeType, $totalLineCount, $countedLineCount, $reconciledLineCount, $createdBy, self::utc($createdAt), $startedBy, self::nullableUtc($startedAt), $finalizationStartedBy, self::nullableUtc($finalizationStartedAt), $completedBy, self::nullableUtc($completedAt), $cancelledBy, self::nullableUtc($cancelledAt), $version);
    }

    private static function utc(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTimezone(new DateTimeZone('UTC'));
    }

    private static function nullableUtc(?DateTimeImmutable $date): ?DateTimeImmutable
    {
        return $date?->setTimezone(new DateTimeZone('UTC'));
    }

    public function id(): StockCountId
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
    public function status(): StockCountStatus
    {
        return $this->status;
    }
    public function mode(): StockCountMode
    {
        return $this->mode;
    }
    public function scopeType(): StockCountScopeType
    {
        return $this->scopeType;
    }
    public function totalLineCount(): int
    {
        return $this->totalLineCount;
    }
    public function countedLineCount(): int
    {
        return $this->countedLineCount;
    }
    public function reconciledLineCount(): int
    {
        return $this->reconciledLineCount;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function startedBy(): ?ActorId
    {
        return $this->startedBy;
    }
    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }
    public function finalizationStartedBy(): ?ActorId
    {
        return $this->finalizationStartedBy;
    }
    public function finalizationStartedAt(): ?DateTimeImmutable
    {
        return $this->finalizationStartedAt;
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
    public function version(): int
    {
        return $this->version;
    }
}
