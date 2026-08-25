<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Quantity\Quantity;

final class ProductPackaging
{
    private function __construct(
        private readonly ProductPackagingId $id,
        private readonly OrganizationId $organizationId,
        private readonly ProductId $productId,
        private readonly bool $base,
        private readonly ProductPackagingCode $code,
        private ProductPackagingName $name,
        private readonly UnitOfMeasureId $unitId,
        private readonly ConversionFactor $conversionFactor,
        private readonly ProductPackagingPrecision $precision,
        private Quantity $minimumQuantity,
        private Quantity $quantityIncrement,
        private bool $allowedForSale,
        private bool $allowedForPurchase,
        private ProductPackagingStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly ActorId $createdBy,
        private ?DateTimeImmutable $updatedAt,
        private ?ActorId $updatedBy,
        private int $version,
    ) {
        self::assertPositiveQuantity($minimumQuantity, 'Minimum quantity');
        self::assertPositiveQuantity($quantityIncrement, 'Quantity increment');
        self::assertCompatiblePrecision($minimumQuantity, $precision, 'Minimum quantity');
        self::assertCompatiblePrecision($quantityIncrement, $precision, 'Quantity increment');
        if ((null === $updatedAt) !== (null === $updatedBy)) {
            throw new InvalidArgumentException('Product packaging update audit fields must both be null or both be set.');
        }
        if ($version < 1) {
            throw new InvalidArgumentException('Product packaging version must be positive.');
        }
    }

    public static function createAdditional(
        ProductPackagingId $id,
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingCode $code,
        ProductPackagingName $name,
        UnitOfMeasureId $unitId,
        ConversionFactor $conversionFactor,
        ProductPackagingPrecision $precision,
        Quantity $minimumQuantity,
        Quantity $quantityIncrement,
        bool $allowedForSale,
        bool $allowedForPurchase,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        return self::createPackaging(
            $id,
            $organizationId,
            $productId,
            false,
            $code,
            $name,
            $unitId,
            $conversionFactor,
            $precision,
            $minimumQuantity,
            $quantityIncrement,
            $allowedForSale,
            $allowedForPurchase,
            $actorId,
            $occurredAt,
        );
    }

    public static function createBase(
        ProductPackagingId $id,
        Product $product,
        ProductPackagingCode $code,
        ProductPackagingName $name,
        ConversionFactor $conversionFactor,
        ProductPackagingPrecision $precision,
        Quantity $minimumQuantity,
        Quantity $quantityIncrement,
        bool $allowedForSale,
        bool $allowedForPurchase,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        if (!$conversionFactor->isOne()) {
            throw new InvalidArgumentException('Base product packaging conversion factor must equal one.');
        }

        return self::createPackaging(
            $id,
            $product->organizationId(),
            $product->id(),
            true,
            $code,
            $name,
            $product->baseUnitId(),
            $conversionFactor,
            $precision,
            $minimumQuantity,
            $quantityIncrement,
            $allowedForSale,
            $allowedForPurchase,
            $actorId,
            $occurredAt,
        );
    }

    private static function createPackaging(
        ProductPackagingId $id,
        OrganizationId $organizationId,
        ProductId $productId,
        bool $base,
        ProductPackagingCode $code,
        ProductPackagingName $name,
        UnitOfMeasureId $unitId,
        ConversionFactor $conversionFactor,
        ProductPackagingPrecision $precision,
        Quantity $minimumQuantity,
        Quantity $quantityIncrement,
        bool $allowedForSale,
        bool $allowedForPurchase,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            $organizationId,
            $productId,
            $base,
            $code,
            $name,
            $unitId,
            $conversionFactor,
            $precision,
            $minimumQuantity,
            $quantityIncrement,
            $allowedForSale,
            $allowedForPurchase,
            ProductPackagingStatus::Active,
            self::utc($occurredAt),
            $actorId,
            null,
            null,
            1,
        );
    }

    public static function reconstitute(
        ProductPackagingId $id,
        OrganizationId $organizationId,
        ProductId $productId,
        bool $base,
        ProductPackagingCode $code,
        ProductPackagingName $name,
        UnitOfMeasureId $unitId,
        ConversionFactor $conversionFactor,
        ProductPackagingPrecision $precision,
        Quantity $minimumQuantity,
        Quantity $quantityIncrement,
        bool $allowedForSale,
        bool $allowedForPurchase,
        ProductPackagingStatus $status,
        DateTimeImmutable $createdAt,
        ActorId $createdBy,
        ?DateTimeImmutable $updatedAt,
        ?ActorId $updatedBy,
        int $version,
    ): self {
        return new self($id, $organizationId, $productId, $base, $code, $name, $unitId, $conversionFactor, $precision, $minimumQuantity, $quantityIncrement, $allowedForSale, $allowedForPurchase, $status, self::utc($createdAt), $createdBy, null !== $updatedAt ? self::utc($updatedAt) : null, $updatedBy, $version);
    }

    public function updateCommercialSettings(
        ProductPackagingName $name,
        Quantity $minimumQuantity,
        Quantity $quantityIncrement,
        bool $allowedForSale,
        bool $allowedForPurchase,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireNotArchived('An archived product packaging cannot be updated.');
        self::assertPositiveQuantity($minimumQuantity, 'Minimum quantity');
        self::assertPositiveQuantity($quantityIncrement, 'Quantity increment');
        self::assertCompatiblePrecision($minimumQuantity, $this->precision, 'Minimum quantity');
        self::assertCompatiblePrecision($quantityIncrement, $this->precision, 'Quantity increment');
        $this->name = $name;
        $this->minimumQuantity = $minimumQuantity;
        $this->quantityIncrement = $quantityIncrement;
        $this->allowedForSale = $allowedForSale;
        $this->allowedForPurchase = $allowedForPurchase;
        $this->changedBy($actorId, $occurredAt);
    }

    public function deactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(ProductPackagingStatus::Active, 'Only an active product packaging can be deactivated.');
        $this->status = ProductPackagingStatus::Inactive;
        $this->changedBy($actorId, $occurredAt);
    }

    public function activate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(ProductPackagingStatus::Inactive, 'Only an inactive product packaging can be activated.');
        $this->status = ProductPackagingStatus::Active;
        $this->changedBy($actorId, $occurredAt);
    }

    public function archive(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireNotArchived('Product packaging is already archived.');
        $this->status = ProductPackagingStatus::Archived;
        $this->changedBy($actorId, $occurredAt);
    }

    public function ensureAvailableForSale(): void
    {
        $this->requireStatus(ProductPackagingStatus::Active, 'Only an active product packaging can be sold.');
        if (!$this->allowedForSale) {
            throw new LogicException('Product packaging is not allowed for sale.');
        }
    }

    public function ensureAvailableForPurchase(): void
    {
        $this->requireStatus(ProductPackagingStatus::Active, 'Only an active product packaging can be purchased.');
        if (!$this->allowedForPurchase) {
            throw new LogicException('Product packaging is not allowed for purchase.');
        }
    }

    public function id(): ProductPackagingId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function isBase(): bool
    {
        return $this->base;
    }
    public function code(): ProductPackagingCode
    {
        return $this->code;
    }
    public function name(): ProductPackagingName
    {
        return $this->name;
    }
    public function unitId(): UnitOfMeasureId
    {
        return $this->unitId;
    }
    public function conversionFactor(): ConversionFactor
    {
        return $this->conversionFactor;
    }
    public function precision(): ProductPackagingPrecision
    {
        return $this->precision;
    }
    public function minimumQuantity(): Quantity
    {
        return $this->minimumQuantity;
    }
    public function quantityIncrement(): Quantity
    {
        return $this->quantityIncrement;
    }
    public function allowedForSale(): bool
    {
        return $this->allowedForSale;
    }
    public function allowedForPurchase(): bool
    {
        return $this->allowedForPurchase;
    }
    public function status(): ProductPackagingStatus
    {
        return $this->status;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function updatedBy(): ?ActorId
    {
        return $this->updatedBy;
    }
    public function version(): int
    {
        return $this->version;
    }

    private static function assertPositiveQuantity(Quantity $quantity, string $field): void
    {
        if ($quantity->isZero() || $quantity->isNegative()) {
            throw new InvalidArgumentException(sprintf('%s must be greater than zero.', $field));
        }
    }

    private static function assertCompatiblePrecision(Quantity $quantity, ProductPackagingPrecision $precision, string $field): void
    {
        $fraction = strchr($quantity->toString(), '.');
        $scale = false === $fraction ? 0 : strlen(rtrim(substr($fraction, 1), '0'));
        if ($scale > $precision->value()) {
            throw new InvalidArgumentException(sprintf('%s exceeds product packaging precision.', $field));
        }
    }

    private function changedBy(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->updatedAt = self::utc($occurredAt);
        $this->updatedBy = $actorId;
        ++$this->version;
    }

    private function requireStatus(ProductPackagingStatus $status, string $message): void
    {
        if ($this->status !== $status) {
            throw new LogicException($message);
        }
    }

    private function requireNotArchived(string $message): void
    {
        if (ProductPackagingStatus::Archived === $this->status) {
            throw new LogicException($message);
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
