<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Catalog\Domain\Product\Product;

#[ORM\Entity]
#[ORM\Table(name: 'products', schema: 'catalog')]
#[ORM\UniqueConstraint(name: 'product_code_tenant_unique', columns: ['organization_id', 'product_code'])]
#[ORM\UniqueConstraint(name: 'product_tenant_id_unique', columns: ['organization_id', 'id'])]
final class ProductRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(length: 64)]
        private string $productCode,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $description,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(length: 16)]
        private string $type,
        #[ORM\Column(type: 'guid')]
        private string $baseUnitId,
        #[ORM\Column]
        private bool $inventoryTracked,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $taxCategoryId,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $categoryId,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $activatedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $activatedBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $updatedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $updatedBy,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(Product $product): self
    {
        return new self(
            $product->id()->toString(),
            $product->organizationId()->toString(),
            $product->productCode()->value(),
            $product->name()->value(),
            $product->description(),
            $product->status()->value,
            $product->type()->value,
            $product->baseUnitId()->toString(),
            $product->inventoryTracked(),
            $product->taxCategoryId()?->toString(),
            $product->categoryId()?->toString(),
            $product->createdAt(),
            $product->createdBy()->toString(),
            $product->activatedAt(),
            $product->activatedBy()?->toString(),
            $product->updatedAt(),
            $product->updatedBy()?->toString(),
            $product->version(),
        );
    }

    public function synchronize(Product $product): void
    {
        $current = self::fromAggregate($product);
        $this->productCode = $current->productCode;
        $this->name = $current->name;
        $this->description = $current->description;
        $this->status = $current->status;
        $this->type = $current->type;
        $this->baseUnitId = $current->baseUnitId;
        $this->inventoryTracked = $current->inventoryTracked;
        $this->taxCategoryId = $current->taxCategoryId;
        $this->categoryId = $current->categoryId;
        $this->activatedAt = $current->activatedAt;
        $this->activatedBy = $current->activatedBy;
        $this->updatedAt = $current->updatedAt;
        $this->updatedBy = $current->updatedBy;
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function productCode(): string
    {
        return $this->productCode;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function description(): ?string
    {
        return $this->description;
    }
    public function status(): string
    {
        return $this->status;
    }
    public function type(): string
    {
        return $this->type;
    }
    public function baseUnitId(): string
    {
        return $this->baseUnitId;
    }
    public function inventoryTracked(): bool
    {
        return $this->inventoryTracked;
    }
    public function taxCategoryId(): ?string
    {
        return $this->taxCategoryId;
    }
    public function categoryId(): ?string
    {
        return $this->categoryId;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
    }
    public function activatedAt(): ?DateTimeImmutable
    {
        return $this->activatedAt;
    }
    public function activatedBy(): ?string
    {
        return $this->activatedBy;
    }
    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function updatedBy(): ?string
    {
        return $this->updatedBy;
    }
    public function version(): int
    {
        return $this->version;
    }
}
