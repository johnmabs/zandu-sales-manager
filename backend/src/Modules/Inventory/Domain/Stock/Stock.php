<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\Stock;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StockId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Quantity\Quantity;

final class Stock
{
    private function __construct(
        private readonly StockId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private readonly ProductId $productId,
        private Quantity $quantityOnHand,
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
        Quantity $quantityOnHand,
    ): self {
        return new self($id, $organizationId, $storeId, $productId, $quantityOnHand, false, null, null, 1);
    }

    public static function reconstitute(
        StockId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
        Quantity $quantityOnHand,
        bool $initialized,
        ?DateTimeImmutable $initializedAt,
        ?ActorId $initializedBy,
        int $version,
    ): self {
        return new self($id, $organizationId, $storeId, $productId, $quantityOnHand, $initialized, $initializedAt, $initializedBy, $version);
    }

    public function initialize(Quantity $quantity, ActorId $actorId, DateTimeImmutable $occurredAt): void
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

    public function increase(Quantity $quantity): void
    {
        $this->requireInitialized();
        self::assertPositive($quantity);
        $this->quantityOnHand = $this->quantityOnHand->add($quantity);
        ++$this->version;
    }

    public function decrease(Quantity $quantity): void
    {
        $this->requireInitialized();
        self::assertPositive($quantity);
        $result = $this->quantityOnHand->subtract($quantity);
        self::assertQuantity($result);
        $this->quantityOnHand = $result;
        ++$this->version;
    }

    public function id(): StockId { return $this->id; }
    public function organizationId(): OrganizationId { return $this->organizationId; }
    public function storeId(): StoreId { return $this->storeId; }
    public function productId(): ProductId { return $this->productId; }
    public function quantityOnHand(): Quantity { return $this->quantityOnHand; }
    public function initialized(): bool { return $this->initialized; }
    public function initializedAt(): ?DateTimeImmutable { return $this->initializedAt; }
    public function initializedBy(): ?ActorId { return $this->initializedBy; }
    public function version(): int { return $this->version; }

    private function requireInitialized(): void
    {
        if (!$this->initialized) {
            throw new LogicException('Stock must be initialized before quantity changes.');
        }
    }

    private static function assertPositive(Quantity $quantity): void
    {
        if ($quantity->isNegative() || $quantity->isZero()) {
            throw new InvalidArgumentException('Stock operation quantity must be positive.');
        }
    }

    private static function assertQuantity(Quantity $quantity): void
    {
        if ($quantity->isNegative()) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
