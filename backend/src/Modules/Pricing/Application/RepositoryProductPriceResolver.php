<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use DateTimeImmutable;
use Zandu\Modules\Pricing\Application\Contract\ProductPriceResolver;
use Zandu\Modules\Pricing\Application\Contract\ResolvedProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceNotFound;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceRepository;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class RepositoryProductPriceResolver implements ProductPriceResolver
{
    public function __construct(private ProductPriceRepository $productPrices) {}

    public function resolve(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
        DateTimeImmutable $businessInstant,
    ): ResolvedProductPrice {
        $productPrice = $this->productPrices->findEffective(
            $organizationId,
            $productId,
            $packagingId,
            $businessInstant,
        ) ?? throw ProductPriceNotFound::forTarget($organizationId, $productId, $packagingId);

        return new ResolvedProductPrice(
            $productPrice->priceListId(),
            $productPrice->id(),
            $productPrice->amount()->amount(),
            $productPrice->amount()->currency(),
            $productPrice->version(),
        );
    }
}
