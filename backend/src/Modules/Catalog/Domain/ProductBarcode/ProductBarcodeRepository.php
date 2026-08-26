<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductBarcode;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductBarcodeId;

interface ProductBarcodeRepository
{
    public function save(ProductBarcode $barcode): void;
    public function findByBarcode(OrganizationId $organizationId, Barcode $barcode): ?ProductBarcode;
    public function get(OrganizationId $organizationId, ProductBarcodeId $id): ProductBarcode;
}
