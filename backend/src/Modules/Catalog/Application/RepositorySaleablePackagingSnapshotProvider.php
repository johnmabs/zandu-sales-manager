<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Application\Contract\SaleablePackagingSnapshot;
use Zandu\Modules\Catalog\Application\Contract\SaleablePackagingSnapshotProvider;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingNotFound;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class RepositorySaleablePackagingSnapshotProvider implements SaleablePackagingSnapshotProvider
{
    public function __construct(private ProductPackagingRepository $packagings) {}

    public function provide(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
    ): SaleablePackagingSnapshot {
        $packaging = $this->packagings->get($organizationId, $packagingId);
        if (!$packaging->productId()->equals($productId)) {
            throw ProductPackagingNotFound::withId($packagingId);
        }
        $packaging->ensureAvailableForSale();

        return new SaleablePackagingSnapshot(
            $packaging->productId(),
            $packaging->id(),
            $packaging->conversionFactor()->value(),
            $packaging->version(),
        );
    }
}
