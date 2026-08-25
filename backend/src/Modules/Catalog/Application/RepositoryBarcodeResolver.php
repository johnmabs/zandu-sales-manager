<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductBarcode\Barcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeRepository;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeStatus;
use Zandu\SharedKernel\Identity\OrganizationId;

final readonly class RepositoryBarcodeResolver implements BarcodeResolver
{
    public function __construct(private ProductBarcodeRepository $barcodes) {}

    public function resolve(OrganizationId $organizationId, Barcode $barcode): ?BarcodeResolution
    {
        $productBarcode = $this->barcodes->findByBarcode($organizationId, $barcode);

        if (null === $productBarcode || ProductBarcodeStatus::Active !== $productBarcode->status()) {
            return null;
        }

        return new BarcodeResolution($productBarcode->productId(), $productBarcode->packagingId());
    }
}
