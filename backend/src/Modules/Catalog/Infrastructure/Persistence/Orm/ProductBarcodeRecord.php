<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcode;

#[ORM\Entity]
#[ORM\Table(name: 'product_barcodes', schema: 'catalog')]
#[ORM\UniqueConstraint(name: 'product_barcode_tenant_unique', columns: ['organization_id', 'normalized_barcode'])]
final class ProductBarcodeRecord
{
    private function __construct(#[ORM\Id] #[ORM\Column(type: 'guid')] private string $id, #[ORM\Column(type: 'guid')] private string $organizationId, #[ORM\Column(type: 'guid')] private string $productId, #[ORM\Column(type: 'guid')] private string $packagingId, #[ORM\Column(length: 128)] private string $rawBarcode, #[ORM\Column(length: 128)] private string $normalizedBarcode, #[ORM\Column(length: 16)] private string $status, #[ORM\Column(type: 'datetimetz_immutable')] private DateTimeImmutable $createdAt, #[ORM\Column(type: 'guid')] private string $createdBy, #[ORM\Column(type: 'datetimetz_immutable', nullable: true)] private ?DateTimeImmutable $removedAt, #[ORM\Column(type: 'guid', nullable: true)] private ?string $removedBy, #[ORM\Version] #[ORM\Column(type: 'integer')] private int $version) {}
    public static function fromAggregate(ProductBarcode $b): self
    {
        return new self($b->id()->toString(), $b->organizationId()->toString(), $b->productId()->toString(), $b->packagingId()->toString(), $b->barcode()->raw(), $b->barcode()->normalized(), $b->status()->value, $b->createdAt(), $b->createdBy()->toString(), $b->removedAt(), $b->removedBy()?->toString(), $b->version());
    }
    public function synchronize(ProductBarcode $b): void
    {
        $this->status = $b->status()->value;
        $this->removedAt = $b->removedAt();
        $this->removedBy = $b->removedBy()?->toString();
    }
    public function id(): string
    {
        return $this->id;
    } public function organizationId(): string
    {
        return $this->organizationId;
    } public function productId(): string
    {
        return $this->productId;
    } public function packagingId(): string
    {
        return $this->packagingId;
    } public function rawBarcode(): string
    {
        return $this->rawBarcode;
    } public function normalizedBarcode(): string
    {
        return $this->normalizedBarcode;
    } public function status(): string
    {
        return $this->status;
    } public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    } public function createdBy(): string
    {
        return $this->createdBy;
    } public function removedAt(): ?DateTimeImmutable
    {
        return $this->removedAt;
    } public function removedBy(): ?string
    {
        return $this->removedBy;
    } public function version(): int
    {
        return $this->version;
    }
}
