<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class ProductBarcodeQueryService
{
    public function __construct(private ProductBarcodeRepository $barcodes, private AuthorizationService $authorization) {}

    /** @return list<ProductBarcodeView> */
    public function list(ProductId $productId, ProductPackagingId $packagingId, ActorContext $actor): array
    {
        $this->authorization->authorize($actor, PermissionCode::ProductRead, ResourceScope::organization($actor->organizationId()));
        return array_map(
            static fn($barcode): ProductBarcodeView => new ProductBarcodeView(
                $barcode->id()->toString(),
                $barcode->productId()->toString(),
                $barcode->packagingId()->toString(),
                $barcode->barcode()->raw(),
                $barcode->status()->value,
                $barcode->version(),
            ),
            array_filter(
                $this->barcodes->findAllByPackaging($actor->organizationId(), $packagingId),
                static fn($barcode): bool => $barcode->productId()->equals($productId),
            ),
        );
    }
}
