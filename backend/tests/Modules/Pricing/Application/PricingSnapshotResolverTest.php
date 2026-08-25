<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Pricing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\Contract\SaleablePackagingSnapshot;
use Zandu\Modules\Catalog\Application\Contract\SaleablePackagingSnapshotProvider;
use Zandu\Modules\Pricing\Application\Contract\ProductPriceResolver;
use Zandu\Modules\Pricing\Application\Contract\ResolvedProductPrice;
use Zandu\Modules\Pricing\Application\RepositoryPricingSnapshotResolver;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListRepository;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListScope;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListStatus;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Money\Currency;

final class PricingSnapshotResolverTest extends TestCase
{
    public function testItCombinesExactCatalogAndPricingSourcesWithoutSalesDomainTypes(): void
    {
        $ids = new SymfonyUuidFactory();
        $decimals = new BrickDecimalFactory();
        $organizationId = OrganizationId::fromString('0198e6a1-b2a4-7b6e-8e0e-608484906502', $ids);
        $productId = ProductId::fromString('0198e6b1-147c-72d5-b75a-a936797ff9c8', $ids);
        $packagingId = ProductPackagingId::fromString('0198e6c1-147c-72d5-b75a-a936797ff9c8', $ids);
        $priceListId = PriceListId::fromString('0198e6d1-147c-72d5-b75a-a936797ff9c8', $ids);
        $productPriceId = ProductPriceId::fromString('0198e6e1-147c-72d5-b75a-a936797ff9c8', $ids);
        $currency = Currency::fromCode('XAF');
        $priceList = PriceList::reconstitute(
            $priceListId,
            $organizationId,
            PriceListCode::fromString('RETAIL'),
            PriceListName::fromString('Tarif standard'),
            $currency,
            PriceListStatus::Active,
            PriceListScope::Organization,
            null,
            null,
            PriceListPriority::fromInt(0),
            new DateTimeImmutable('2026-08-26T08:00:00Z'),
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $ids),
            4,
        );
        $resolver = new RepositoryPricingSnapshotResolver(
            new SnapshotPackagingProvider(new SaleablePackagingSnapshot($productId, $packagingId, $decimals->fromString('6'), 3)),
            new SnapshotPriceResolver(new ResolvedProductPrice($priceListId, $productPriceId, $decimals->fromString('1500'), $currency, 7)),
            new SnapshotPriceListRepository($priceList),
        );

        $snapshot = $resolver->resolveSnapshot(
            $organizationId,
            $productId,
            $packagingId,
            new DateTimeImmutable('2026-08-26T10:00:00Z'),
        );

        self::assertTrue($snapshot->productId()->equals($productId));
        self::assertTrue($snapshot->packagingId()->equals($packagingId));
        self::assertSame('6', $snapshot->packagingFactor()->toString());
        self::assertTrue($snapshot->priceListId()->equals($priceListId));
        self::assertTrue($snapshot->productPriceId()->equals($productPriceId));
        self::assertSame('1500', $snapshot->priceAmount()->toString());
        self::assertSame('XAF', $snapshot->currency()->code());
        self::assertSame(3, $snapshot->packagingSourceVersion());
        self::assertSame(4, $snapshot->priceListSourceVersion());
        self::assertSame(7, $snapshot->productPriceSourceVersion());
    }
}

final readonly class SnapshotPackagingProvider implements SaleablePackagingSnapshotProvider
{
    public function __construct(private SaleablePackagingSnapshot $snapshot) {}

    public function provide(OrganizationId $organizationId, ProductId $productId, ProductPackagingId $packagingId): SaleablePackagingSnapshot
    {
        return $this->snapshot;
    }
}

final readonly class SnapshotPriceResolver implements ProductPriceResolver
{
    public function __construct(private ResolvedProductPrice $resolved) {}

    public function resolve(OrganizationId $organizationId, ProductId $productId, ProductPackagingId $packagingId, DateTimeImmutable $businessInstant): ResolvedProductPrice
    {
        return $this->resolved;
    }
}

final readonly class SnapshotPriceListRepository implements PriceListRepository
{
    public function __construct(private PriceList $priceList) {}

    public function save(PriceList $priceList): void {}

    public function get(OrganizationId $organizationId, PriceListId $id): PriceList
    {
        return $this->priceList;
    }

    public function find(OrganizationId $organizationId, PriceListId $id): PriceList
    {
        return $this->priceList;
    }

    public function findByCode(OrganizationId $organizationId, PriceListCode $code): PriceList
    {
        return $this->priceList;
    }
}
