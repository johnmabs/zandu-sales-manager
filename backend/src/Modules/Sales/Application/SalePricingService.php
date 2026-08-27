<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use DateTimeImmutable;
use Zandu\Modules\Pricing\Application\Contract\{PricingSnapshot, PricingSnapshotResolver};
use Zandu\Modules\Sales\Domain\{Sale,SalesRuleViolation};
use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,ProductPackagingId};

final readonly class SalePricingService
{
    public function __construct(private PricingSnapshotResolver $resolver) {}

    public function resolve(OrganizationId $organizationId, ProductId $productId, ProductPackagingId $packagingId, DateTimeImmutable $businessInstant, ?int $expectedSourceVersion = null): PricingSnapshot
    {
        $snapshot = $this->resolver->resolveSnapshot($organizationId, $productId, $packagingId, $businessInstant);
        if (null !== $expectedSourceVersion && $expectedSourceVersion !== $snapshot->productPriceSourceVersion()) {
            throw SalesRuleViolation::with('SALE_PRICING_CHANGED', 'Sale pricing has changed.');
        }
        return $snapshot;
    }

    public function assertCurrent(Sale $sale, DateTimeImmutable $businessInstant): void
    {
        foreach ($sale->lines() as $line) {
            $versions = $line->sourceVersions();
            $snapshot = $this->resolve(
                $sale->organizationId(),
                $line->productId(),
                $line->productPackagingId(),
                $businessInstant,
                isset($versions['productPrice']) ? (int) $versions['productPrice'] : null,
            );
            if ($line->priceListId() !== $snapshot->priceListId()->toString()
                || $line->productPriceId() !== $snapshot->productPriceId()->toString()
                || !$line->unitPrice()->amount()->equals($snapshot->priceAmount())
                || (isset($versions['packaging']) && (int) $versions['packaging'] !== $snapshot->packagingSourceVersion())
                || (isset($versions['priceList']) && (int) $versions['priceList'] !== $snapshot->priceListSourceVersion())) {
                throw SalesRuleViolation::with('SALE_PRICING_CHANGED', 'Sale pricing has changed.');
            }
        }
    }
}
