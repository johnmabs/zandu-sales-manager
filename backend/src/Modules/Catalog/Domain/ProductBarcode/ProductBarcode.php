<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductBarcode;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Zandu\Modules\Catalog\Domain\ProductBarcode\Event\ProductBarcodeAdded;
use Zandu\Modules\Catalog\Domain\ProductBarcode\Event\ProductBarcodeEvent;
use Zandu\Modules\Catalog\Domain\ProductBarcode\Event\ProductBarcodeRemoved;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductBarcodeId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class ProductBarcode implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @var list<ProductBarcodeEvent> */
    private array $recordedEvents = [];
    private function __construct(private readonly ProductBarcodeId $id, private readonly OrganizationId $organizationId, private readonly ProductId $productId, private readonly ProductPackagingId $packagingId, private readonly Barcode $barcode, private ProductBarcodeStatus $status, private readonly DateTimeImmutable $createdAt, private readonly ActorId $createdBy, private ?DateTimeImmutable $removedAt, private ?ActorId $removedBy, private int $version)
    {
        $this->assertValidVersion();
    }
    public static function add(ProductBarcodeId $id, ProductPackaging $packaging, Barcode $barcode, ActorId $actor, DateTimeImmutable $at): self
    {
        $barcodeAggregate = new self($id, $packaging->organizationId(), $packaging->productId(), $packaging->id(), $barcode, ProductBarcodeStatus::Active, self::utc($at), $actor, null, null, 1);
        $barcodeAggregate->recordedEvents[] = new ProductBarcodeAdded($barcodeAggregate->organizationId, $id, $actor, $barcodeAggregate->createdAt);
        return $barcodeAggregate;
    }
    public static function reconstitute(ProductBarcodeId $id, OrganizationId $organizationId, ProductId $productId, ProductPackagingId $packagingId, Barcode $barcode, ProductBarcodeStatus $status, DateTimeImmutable $createdAt, ActorId $createdBy, ?DateTimeImmutable $removedAt, ?ActorId $removedBy, int $version): self
    {
        return new self($id, $organizationId, $productId, $packagingId, $barcode, $status, self::utc($createdAt), $createdBy, null !== $removedAt ? self::utc($removedAt) : null, $removedBy, $version);
    }
    public function remove(ActorId $actor, DateTimeImmutable $at): void
    {
        if (ProductBarcodeStatus::Removed === $this->status) {
            throw new LogicException('Product barcode is already removed.');
        } $this->status = ProductBarcodeStatus::Removed;
        $removedAt = self::utc($at);
        $this->removedAt = $removedAt;
        $this->removedBy = $actor;
        $this->advanceVersion();
        $this->recordedEvents[] = new ProductBarcodeRemoved($this->organizationId, $this->id, $actor, $removedAt);
    }
    /** @return list<ProductBarcodeEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }
    public function id(): ProductBarcodeId
    {
        return $this->id;
    } public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    } public function productId(): ProductId
    {
        return $this->productId;
    } public function packagingId(): ProductPackagingId
    {
        return $this->packagingId;
    } public function barcode(): Barcode
    {
        return $this->barcode;
    } public function status(): ProductBarcodeStatus
    {
        return $this->status;
    } public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    } public function createdBy(): ActorId
    {
        return $this->createdBy;
    } public function removedAt(): ?DateTimeImmutable
    {
        return $this->removedAt;
    } public function removedBy(): ?ActorId
    {
        return $this->removedBy;
    }
    private static function utc(DateTimeImmutable $at): DateTimeImmutable
    {
        return $at->setTimezone(new DateTimeZone('UTC'));
    }
}
