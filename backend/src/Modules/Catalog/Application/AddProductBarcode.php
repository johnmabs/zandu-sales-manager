<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class AddProductBarcode
{
    public function __construct(public ProductPackagingId $packagingId, public string $barcode, public ActorContext $actorContext) {}
}
