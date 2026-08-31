<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
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
        /** @var list<\Zandu\SharedKernel\Identity\ProductId> */
        private array $requestedProductIds,
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

    /** @param list<\Zandu\SharedKernel\Identity\ProductId> $requestedProductIds */
    public static function create(StockCountId $id, OrganizationId $organizationId, StoreId $storeId, StockCountScopeType $scopeType, ActorId $createdBy, DateTimeImmutable $createdAt, StockCountMode $mode = StockCountMode::Blind, array $requestedProductIds = []): self
    {
        if (StockCountScopeType::Full === $scopeType && [] !== $requestedProductIds) {
            throw \Zandu\Modules\Inventory\Domain\InventoryRuleViolation::with('STOCK_COUNT_FULL_SCOPE_PRODUCTS_INVALID', 'A full stock count resolves its product scope when it starts.');
        }
        if (StockCountScopeType::Partial === $scopeType && [] === $requestedProductIds) {
            throw \Zandu\Modules\Inventory\Domain\InventoryRuleViolation::with('STOCK_COUNT_PARTIAL_SCOPE_EMPTY', 'A partial stock count requires at least one product.');
        }
        $uniqueProducts = [];
        foreach ($requestedProductIds as $productId) {
            if (isset($uniqueProducts[$productId->toString()])) {
                throw \Zandu\Modules\Inventory\Domain\InventoryRuleViolation::with('STOCK_COUNT_PRODUCT_DUPLICATE', 'A product can occur only once in a stock count scope.');
            }
            $uniqueProducts[$productId->toString()] = true;
        }

        return new self($id, $organizationId, $storeId, StockCountStatus::Draft, $mode, $scopeType, $requestedProductIds, 0, 0, 0, $createdBy, self::utc($createdAt), null, null, null, null, null, null, null, null, 1);
    }

    /** @param list<\Zandu\SharedKernel\Identity\ProductId> $requestedProductIds */
    public static function reconstitute(StockCountId $id, OrganizationId $organizationId, StoreId $storeId, StockCountStatus $status, StockCountMode $mode, StockCountScopeType $scopeType, array $requestedProductIds, int $totalLineCount, int $countedLineCount, int $reconciledLineCount, ActorId $createdBy, DateTimeImmutable $createdAt, ?ActorId $startedBy, ?DateTimeImmutable $startedAt, ?ActorId $finalizationStartedBy, ?DateTimeImmutable $finalizationStartedAt, ?ActorId $completedBy, ?DateTimeImmutable $completedAt, ?ActorId $cancelledBy, ?DateTimeImmutable $cancelledAt, int $version): self
    {
        return new self($id, $organizationId, $storeId, $status, $mode, $scopeType, $requestedProductIds, $totalLineCount, $countedLineCount, $reconciledLineCount, $createdBy, self::utc($createdAt), $startedBy, self::nullableUtc($startedAt), $finalizationStartedBy, self::nullableUtc($finalizationStartedAt), $completedBy, self::nullableUtc($completedAt), $cancelledBy, self::nullableUtc($cancelledAt), $version);
    }

    public function start(ActorId $actorId, DateTimeImmutable $at, int $totalLineCount): void
    {
        if (StockCountStatus::Draft !== $this->status) {
            throw InventoryRuleViolation::with('STOCK_COUNT_NOT_DRAFT', 'Only a draft stock count can be started.');
        }
        if ($totalLineCount < 0) {
            throw new \InvalidArgumentException('Stock count line total cannot be negative.');
        }
        $this->status = StockCountStatus::Open;
        $this->totalLineCount = $totalLineCount;
        $this->startedBy = $actorId;
        $this->startedAt = self::utc($at);
        ++$this->version;
    }

    public function registerCountedLines(int $newlyCountedLineCount): void
    {
        if (StockCountStatus::Open !== $this->status) {
            throw InventoryRuleViolation::with('STOCK_COUNT_NOT_OPEN', 'Stock count entries require an open stock count.');
        }
        if ($newlyCountedLineCount < 0 || $this->countedLineCount + $newlyCountedLineCount > $this->totalLineCount) {
            throw new \InvalidArgumentException('Stock count progress is inconsistent.');
        }
        if (0 === $newlyCountedLineCount) {
            return;
        }
        $this->countedLineCount += $newlyCountedLineCount;
        ++$this->version;
    }

    public function beginFinalization(ActorId $actorId, DateTimeImmutable $at): void
    {
        if (StockCountStatus::Open !== $this->status) {
            throw InventoryRuleViolation::with('STOCK_COUNT_NOT_OPEN', 'Only an open stock count can begin finalization.');
        }
        if ($this->countedLineCount !== $this->totalLineCount) {
            throw InventoryRuleViolation::with('STOCK_COUNT_HAS_UNCOUNTED_LINES', 'Every stock count line must be counted before finalization.');
        }
        $this->status = StockCountStatus::Finalizing;
        $this->finalizationStartedBy = $actorId;
        $this->finalizationStartedAt = self::utc($at);
        ++$this->version;
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
    /** @return list<\Zandu\SharedKernel\Identity\ProductId> */
    public function requestedProductIds(): array
    {
        return $this->requestedProductIds;
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
