<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductBarcodeId;

final readonly class RemoveProductBarcode
{
    public function __construct(public ProductBarcodeId $barcodeId, public ActorContext $actorContext) {}
}
