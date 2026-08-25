<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductBarcode\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductBarcodeId;

final readonly class ProductBarcodeAdded implements ProductBarcodeEvent
{
    public function __construct(private OrganizationId $organizationId, private ProductBarcodeId $barcodeId, private ActorId $actorId, private DateTimeImmutable $occurredAt) {} public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    } public function barcodeId(): ProductBarcodeId
    {
        return $this->barcodeId;
    } public function actorId(): ActorId
    {
        return $this->actorId;
    } public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
