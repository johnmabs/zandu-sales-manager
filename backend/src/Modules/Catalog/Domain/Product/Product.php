<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LogicException;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductActivated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductArchived;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductCreated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductDeactivated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductEvent;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductReactivated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductUpdated;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class Product implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<ProductEvent> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ProductId $id,
        private readonly OrganizationId $organizationId,
        private ProductCode $productCode,
        private ProductName $name,
        private ?string $description,
        private ProductStatus $status,
        private ProductType $type,
        private UnitOfMeasureId $baseUnitId,
        private bool $inventoryTracked,
        private ?TaxCategoryId $taxCategoryId,
        private ?CategoryId $categoryId,
        private readonly DateTimeImmutable $createdAt,
        private readonly ActorId $createdBy,
        private ?DateTimeImmutable $activatedAt,
        private ?ActorId $activatedBy,
        private ?DateTimeImmutable $updatedAt,
        private ?ActorId $updatedBy,
        private int $version,
    ) {
        $this->assertValidVersion();
        self::assertTypeAndInventoryTracking($type, $inventoryTracked);
        if ((null === $activatedAt) !== (null === $activatedBy)) {
            throw new InvalidArgumentException('Product activation audit fields must both be null or both be set.');
        }
        if (in_array($status, [ProductStatus::Active, ProductStatus::Inactive], true) && null === $activatedAt) {
            throw new InvalidArgumentException('An active or inactive product must retain its activation audit.');
        }
        if ((null === $updatedAt) !== (null === $updatedBy)) {
            throw new InvalidArgumentException('Product update audit fields must both be null or both be set.');
        }
    }

    public static function createDraft(
        ProductId $id,
        OrganizationId $organizationId,
        ProductCode $productCode,
        ProductName $name,
        ?string $description,
        ProductType $type,
        UnitOfMeasureId $baseUnitId,
        bool $inventoryTracked,
        ?TaxCategoryId $taxCategoryId,
        ?CategoryId $categoryId,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): self {
        $occurredAt = self::utc($occurredAt);
        $product = new self(
            $id,
            $organizationId,
            $productCode,
            $name,
            self::normalizeDescription($description),
            ProductStatus::Draft,
            $type,
            $baseUnitId,
            $inventoryTracked,
            $taxCategoryId,
            $categoryId,
            $occurredAt,
            $actorId,
            null,
            null,
            null,
            null,
            1,
        );
        $product->recordedEvents[] = new ProductCreated($organizationId, $id, $actorId, $occurredAt);

        return $product;
    }

    public static function reconstitute(
        ProductId $id,
        OrganizationId $organizationId,
        ProductCode $productCode,
        ProductName $name,
        ?string $description,
        ProductStatus $status,
        ProductType $type,
        UnitOfMeasureId $baseUnitId,
        bool $inventoryTracked,
        ?TaxCategoryId $taxCategoryId,
        ?CategoryId $categoryId,
        DateTimeImmutable $createdAt,
        ActorId $createdBy,
        ?DateTimeImmutable $activatedAt,
        ?ActorId $activatedBy,
        ?DateTimeImmutable $updatedAt,
        ?ActorId $updatedBy,
        int $version,
    ): self {
        return new self(
            $id,
            $organizationId,
            $productCode,
            $name,
            self::normalizeDescription($description),
            $status,
            $type,
            $baseUnitId,
            $inventoryTracked,
            $taxCategoryId,
            $categoryId,
            self::utc($createdAt),
            $createdBy,
            null !== $activatedAt ? self::utc($activatedAt) : null,
            $activatedBy,
            null !== $updatedAt ? self::utc($updatedAt) : null,
            $updatedBy,
            $version,
        );
    }

    public function updateProfile(
        ProductCode $productCode,
        ProductName $name,
        ?string $description,
        ProductType $type,
        UnitOfMeasureId $baseUnitId,
        bool $inventoryTracked,
        ?TaxCategoryId $taxCategoryId,
        ?CategoryId $categoryId,
        ActorId $actorId,
        DateTimeImmutable $occurredAt,
    ): void {
        $this->requireNotArchived('An archived product cannot be updated.');
        self::assertTypeAndInventoryTracking($type, $inventoryTracked);
        if (null !== $this->activatedAt && !$productCode->equals($this->productCode)) {
            throw new LogicException('Product code is immutable after first activation.');
        }
        if (null !== $this->activatedAt && !$baseUnitId->equals($this->baseUnitId)) {
            throw new LogicException('Product base unit is immutable after first activation.');
        }
        $this->productCode = $productCode;
        $this->name = $name;
        $this->description = self::normalizeDescription($description);
        $this->type = $type;
        $this->baseUnitId = $baseUnitId;
        $this->inventoryTracked = $inventoryTracked;
        $this->taxCategoryId = $taxCategoryId;
        $this->categoryId = $categoryId;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new ProductUpdated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function activate(bool $basePackagingPresent, ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(ProductStatus::Draft, 'Only a draft product can be activated for the first time.');
        if (!$basePackagingPresent) {
            throw new LogicException('A base packaging is required to activate a product.');
        }
        $occurredAt = self::utc($occurredAt);
        $this->status = ProductStatus::Active;
        $this->activatedAt = $occurredAt;
        $this->activatedBy = $actorId;
        $this->markChanged($actorId, $occurredAt);
        $this->recordedEvents[] = new ProductActivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function deactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(ProductStatus::Active, 'Only an active product can be deactivated.');
        $this->status = ProductStatus::Inactive;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new ProductDeactivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function reactivate(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireStatus(ProductStatus::Inactive, 'Only an inactive product can be reactivated.');
        $this->status = ProductStatus::Active;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new ProductReactivated($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function archive(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->requireNotArchived('Product is already archived.');
        $this->status = ProductStatus::Archived;
        $occurredAt = $this->changedBy($actorId, $occurredAt);
        $this->recordedEvents[] = new ProductArchived($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function ensureCommerciallyAvailable(): void
    {
        $this->requireStatus(ProductStatus::Active, 'Only an active product accepts new commercial operations.');
    }

    /** @return list<ProductEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function id(): ProductId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function productCode(): ProductCode
    {
        return $this->productCode;
    }
    public function name(): ProductName
    {
        return $this->name;
    }
    public function description(): ?string
    {
        return $this->description;
    }
    public function status(): ProductStatus
    {
        return $this->status;
    }
    public function type(): ProductType
    {
        return $this->type;
    }
    public function baseUnitId(): UnitOfMeasureId
    {
        return $this->baseUnitId;
    }
    public function inventoryTracked(): bool
    {
        return $this->inventoryTracked;
    }
    public function taxCategoryId(): ?TaxCategoryId
    {
        return $this->taxCategoryId;
    }
    public function categoryId(): ?CategoryId
    {
        return $this->categoryId;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function activatedAt(): ?DateTimeImmutable
    {
        return $this->activatedAt;
    }
    public function activatedBy(): ?ActorId
    {
        return $this->activatedBy;
    }
    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function updatedBy(): ?ActorId
    {
        return $this->updatedBy;
    }

    private static function assertTypeAndInventoryTracking(ProductType $type, bool $inventoryTracked): void
    {
        if (ProductType::Service === $type && $inventoryTracked) {
            throw new LogicException('A service product cannot be inventory tracked.');
        }
    }

    private static function normalizeDescription(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        return null === $normalized || '' === $normalized ? null : $normalized;
    }

    private function changedBy(ActorId $actorId, DateTimeImmutable $occurredAt): DateTimeImmutable
    {
        $occurredAt = self::utc($occurredAt);
        $this->markChanged($actorId, $occurredAt);

        return $occurredAt;
    }

    private function markChanged(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->updatedAt = $occurredAt;
        $this->updatedBy = $actorId;
        $this->advanceVersion();
    }

    private function requireStatus(ProductStatus $status, string $message): void
    {
        if ($this->status !== $status) {
            throw new LogicException($message);
        }
    }

    private function requireNotArchived(string $message): void
    {
        if (ProductStatus::Archived === $this->status) {
            throw new LogicException($message);
        }
    }

    private static function utc(DateTimeImmutable $dateTime): DateTimeImmutable
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'));
    }
}
