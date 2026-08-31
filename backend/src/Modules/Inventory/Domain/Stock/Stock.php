<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\Stock;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StockId;
use Zandu\SharedKernel\Identity\StoreId;

final class Stock
{
    private function __construct(
        private readonly StockId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private readonly ProductId $productId,
        private StockQuantity $quantityOnHand,
        private bool $initialized,
        private ?DateTimeImmutable $initializedAt,
        private ?ActorId $initializedBy,
        private int $version,
    ) {
        self::assertQuantity($quantityOnHand);
        if ($version < 1) {
            throw new InvalidArgumentException('Stock version must be positive.');
        }
        if (!$initialized && (null !== $initializedAt || null !== $initializedBy)) {
            throw new InvalidArgumentException('An uninitialized stock cannot have initialization audit fields.');
        }
        if ((null === $initializedAt) !== (null === $initializedBy)) {
            throw new InvalidArgumentException('Stock initialization audit fields must both be null or set.');
        }
    }

    public static function create(
        StockId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
        StockQuantity $quantityOnHand,
    ): self {
        return new self($id, $organizationId, $storeId, $productId, $quantityOnHand, false, null, null, 1);
    }

    public static function reconstitute(
        StockId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
        StockQuantity $quantityOnHand,
        bool $initialized,
        ?DateTimeImmutable $initializedAt,
        ?ActorId $initializedBy,
        int $version,
    ): self {
        return new self($id, $organizationId, $storeId, $productId, $quantityOnHand, $initialized, $initializedAt, $initializedBy, $version);
    }

    public function initialize(StockQuantity $quantity, ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        if ($this->initialized) {
            throw new LogicException('Stock is already initialized.');
        }
        self::assertQuantity($quantity);
        $this->quantityOnHand = $quantity;
        $this->initialized = true;
        $this->initializedAt = self::utc($occurredAt);
        $this->initializedBy = $actorId;
        ++$this->version;
    }

    public function increase(MovementQuantity $quantity): void
    {
        $this->requireInitialized();
        $this->quantityOnHand = $this->quantityOnHand->add($quantity);
        ++$this->version;
    }

    public function decrease(MovementQuantity $quantity): void
    {
        $this->requireInitialized();
        $result = $this->quantityOnHand->subtract($quantity);
        self::assertQuantity($result);
        $this->quantityOnHand = $result;
        ++$this->version;
    }

    public function reconcile(StockQuantity $expectedQuantity, StockQuantity $countedQuantity): void
    {
        $this->requireInitialized();
        if (!$this->quantityOnHand->value()->equals($expectedQuantity->value())) {
            throw InventoryRuleViolation::with('STOCK_COUNT_SNAPSHOT_CONFLICT', 'Current stock no longer matches the stock count snapshot.');
        }
        if ($this->quantityOnHand->value()->equals($countedQuantity->value())) {
            return;
        }
        $this->quantityOnHand = $countedQuantity;
        ++$this->version;
    }

    public function id(): StockId
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
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function quantityOnHand(): StockQuantity
    {
        return $this->quantityOnHand;
    }
    public function initialized(): bool
    {
        return $this->initialized;
    }
    public function initializedAt(): ?DateTimeImmutable
    {
        return $this->initializedAt;
    }
    public function initializedBy(): ?ActorId
    {
        return $this->initializedBy;
    }
    public function version(): int
    {
        return $this->version;
    }

    private function requireInitialized(): void
    {
        if (!$this->initialized) {
            throw new LogicException('Stock must be initialized before quantity changes.');
        }
    }

    private static function assertQuantity(StockQuantity $quantity): void {}

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
