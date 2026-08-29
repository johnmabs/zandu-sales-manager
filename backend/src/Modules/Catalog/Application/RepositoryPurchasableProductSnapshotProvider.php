<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use LogicException;
use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshot;
use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshotProvider;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingNotFound;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class RepositoryPurchasableProductSnapshotProvider implements PurchasableProductSnapshotProvider
{
    public function __construct(
        private ProductRepository $products,
        private ProductPackagingRepository $packagings,
    ) {}

    public function provide(
        OrganizationId $organizationId,
        ProductId $productId,
        ?ProductPackagingId $packagingId,
    ): PurchasableProductSnapshot {
        $product = $this->products->get($organizationId, $productId);
        $product->ensureCommerciallyAvailable();
        $packaging = null === $packagingId
            ? $this->packagings->findBase($organizationId, $productId)
            : $this->packagings->get($organizationId, $packagingId);
        if (null === $packaging) {
            throw new LogicException('An active base packaging is required for purchase.');
        }
        if (!$packaging->productId()->equals($productId)) {
            throw ProductPackagingNotFound::withId($packaging->id());
        }
        $packaging->ensureAvailableForPurchase();

        return new PurchasableProductSnapshot(
            $productId,
            $packagingId,
            $packaging->conversionFactor()->value(),
            $product->version(),
            $packaging->version(),
        );
    }
}
