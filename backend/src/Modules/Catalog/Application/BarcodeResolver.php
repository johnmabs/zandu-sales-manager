<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductBarcode\Barcode;
use Zandu\SharedKernel\Identity\OrganizationId;

interface BarcodeResolver
{
    public function resolve(OrganizationId $organizationId, Barcode $barcode): ?BarcodeResolution;
}
