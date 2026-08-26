<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use DateTimeImmutable;
use LogicException;
use Zandu\Modules\Pricing\Application\Contract\{PricingSnapshot, PricingSnapshotResolver};
use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,ProductPackagingId};

final readonly class SalePricingService
{
    public function __construct(private PricingSnapshotResolver $resolver) {}

    public function resolve(OrganizationId $organizationId, ProductId $productId, ProductPackagingId $packagingId, DateTimeImmutable $businessInstant, ?int $expectedSourceVersion = null): PricingSnapshot
    {
        $snapshot = $this->resolver->resolveSnapshot($organizationId, $productId, $packagingId, $businessInstant);
        if (null !== $expectedSourceVersion && $expectedSourceVersion !== $snapshot->productPriceSourceVersion()) {
            throw new LogicException('SALE_PRICING_CHANGED');
        }
        return $snapshot;
    }
}
