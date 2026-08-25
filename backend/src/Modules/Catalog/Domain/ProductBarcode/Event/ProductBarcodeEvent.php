<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductBarcode\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductBarcodeId;

interface ProductBarcodeEvent
{
    public function organizationId(): OrganizationId;
    public function barcodeId(): ProductBarcodeId;
    public function actorId(): ActorId;
    public function occurredAt(): DateTimeImmutable;
}
