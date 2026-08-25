<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use DateTimeImmutable;
use Zandu\Modules\Catalog\Application\Contract\SaleablePackagingSnapshotProvider;
use Zandu\Modules\Pricing\Application\Contract\PricingSnapshot;
use Zandu\Modules\Pricing\Application\Contract\PricingSnapshotResolver;
use Zandu\Modules\Pricing\Application\Contract\ProductPriceResolver;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class RepositoryPricingSnapshotResolver implements PricingSnapshotResolver
{
    public function __construct(
        private SaleablePackagingSnapshotProvider $packagings,
        private ProductPriceResolver $prices,
        private PriceListRepository $priceLists,
    ) {}

    public function resolveSnapshot(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
        DateTimeImmutable $businessInstant,
    ): PricingSnapshot {
        $packaging = $this->packagings->provide($organizationId, $productId, $packagingId);
        $price = $this->prices->resolve($organizationId, $productId, $packagingId, $businessInstant);
        $priceList = $this->priceLists->get($organizationId, $price->priceListId());

        return new PricingSnapshot(
            $packaging->productId(),
            $packaging->packagingId(),
            $packaging->conversionFactor(),
            $price->priceListId(),
            $price->productPriceId(),
            $price->amount(),
            $price->currency(),
            $packaging->sourceVersion(),
            $priceList->version(),
            $price->sourceVersion(),
        );
    }
}
