<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;

#[ORM\Entity]
#[ORM\Table(name: 'product_packagings', schema: 'catalog')]
#[ORM\UniqueConstraint(name: 'product_packaging_code_unique', columns: ['organization_id', 'product_id', 'code'])]
final class ProductPackagingRecord
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private string $id,
        #[ORM\Column(type: 'guid')]
        private string $organizationId,
        #[ORM\Column(type: 'guid')]
        private string $productId,
        #[ORM\Column]
        private bool $base,
        #[ORM\Column(length: 64)]
        private string $code,
        #[ORM\Column(length: 160)]
        private string $name,
        #[ORM\Column(type: 'guid')]
        private string $unitId,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $conversionFactor,
        #[ORM\Column(type: 'smallint')]
        private int $precision,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $minimumQuantity,
        #[ORM\Column(type: 'decimal', precision: 30, scale: 12)]
        private string $quantityIncrement,
        #[ORM\Column]
        private bool $allowedForSale,
        #[ORM\Column]
        private bool $allowedForPurchase,
        #[ORM\Column(length: 16)]
        private string $status,
        #[ORM\Column(type: 'datetimetz_immutable')]
        private DateTimeImmutable $createdAt,
        #[ORM\Column(type: 'guid')]
        private string $createdBy,
        #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
        private ?DateTimeImmutable $updatedAt,
        #[ORM\Column(type: 'guid', nullable: true)]
        private ?string $updatedBy,
        #[ORM\Version]
        #[ORM\Column(type: 'integer')]
        private int $version,
    ) {}

    public static function fromAggregate(ProductPackaging $p): self
    {
        return new self($p->id()->toString(), $p->organizationId()->toString(), $p->productId()->toString(), $p->isBase(), $p->code()->value(), $p->name()->value(), $p->unitId()->toString(), $p->conversionFactor()->toString(), $p->precision()->value(), $p->minimumQuantity()->toString(), $p->quantityIncrement()->toString(), $p->allowedForSale(), $p->allowedForPurchase(), $p->status()->value, $p->createdAt(), $p->createdBy()->toString(), $p->updatedAt(), $p->updatedBy()?->toString(), $p->version());
    }

    public function synchronize(ProductPackaging $p): void
    {
        $this->name = $p->name()->value();
        $this->minimumQuantity = $p->minimumQuantity()->toString();
        $this->quantityIncrement = $p->quantityIncrement()->toString();
        $this->allowedForSale = $p->allowedForSale();
        $this->allowedForPurchase = $p->allowedForPurchase();
        $this->status = $p->status()->value;
        $this->updatedAt = $p->updatedAt();
        $this->updatedBy = $p->updatedBy()?->toString();
    }

    public function id(): string
    {
        return $this->id;
    }
    public function organizationId(): string
    {
        return $this->organizationId;
    }
    public function productId(): string
    {
        return $this->productId;
    }
    public function base(): bool
    {
        return $this->base;
    }
    public function code(): string
    {
        return $this->code;
    }
    public function name(): string
    {
        return $this->name;
    }
    public function unitId(): string
    {
        return $this->unitId;
    }
    public function conversionFactor(): string
    {
        return $this->conversionFactor;
    }
    public function precision(): int
    {
        return $this->precision;
    }
    public function minimumQuantity(): string
    {
        return $this->minimumQuantity;
    }
    public function quantityIncrement(): string
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
    public function status(): string
    {
        return $this->status;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function createdBy(): string
    {
        return $this->createdBy;
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
