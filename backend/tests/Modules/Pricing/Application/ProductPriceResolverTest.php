<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Pricing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Pricing\Application\RepositoryProductPriceResolver;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceNotFound;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceRepository;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceStatus;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;

final class ProductPriceResolverTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private OrganizationId $organizationId;
    private ProductId $productId;
    private ProductPackagingId $packagingId;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->organizationId = OrganizationId::fromString('0198e5a1-b2a4-7b6e-8e0e-608484906502', $this->ids);
        $this->productId = ProductId::fromString('0198e5b1-147c-72d5-b75a-a936797ff9c8', $this->ids);
        $this->packagingId = ProductPackagingId::fromString('0198e5c1-147c-72d5-b75a-a936797ff9c8', $this->ids);
    }

    public function testItReturnsAnExactVersionedResolution(): void
    {
        $productPrice = $this->productPrice();
        $resolver = new RepositoryProductPriceResolver(new ResolverProductPriceRepository($productPrice));

        $resolved = $resolver->resolve(
            $this->organizationId,
            $this->productId,
            $this->packagingId,
            new DateTimeImmutable('2026-08-26T10:00:00Z'),
        );

        self::assertTrue($resolved->priceListId()->equals($productPrice->priceListId()));
        self::assertTrue($resolved->productPriceId()->equals($productPrice->id()));
        self::assertSame('1500.00', $resolved->amount()->toString());
        self::assertSame('XAF', $resolved->currency()->code());
        self::assertSame(3, $resolved->sourceVersion());
    }

    public function testItThrowsInsteadOfGuessingAMissingPrice(): void
    {
        $resolver = new RepositoryProductPriceResolver(new ResolverProductPriceRepository(null));

        $this->expectException(ProductPriceNotFound::class);
        $resolver->resolve(
            $this->organizationId,
            $this->productId,
            $this->packagingId,
            new DateTimeImmutable('2026-08-26T10:00:00Z'),
        );
    }

    private function productPrice(): ProductPrice
    {
        return ProductPrice::reconstitute(
            ProductPriceId::fromString('0198e5d1-147c-72d5-b75a-a936797ff9c8', $this->ids),
            $this->organizationId,
            PriceListId::fromString('0198e5e1-147c-72d5-b75a-a936797ff9c8', $this->ids),
            $this->productId,
            $this->packagingId,
            Money::fromString('1500.00', Currency::fromCode('XAF'), new BrickDecimalFactory()),
            ProductPriceStatus::Active,
            null,
            null,
            new DateTimeImmutable('2026-08-26T09:00:00Z'),
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $this->ids),
            3,
        );
    }
}

final readonly class ResolverProductPriceRepository implements ProductPriceRepository
{
    public function __construct(private ?ProductPrice $effective) {}

    public function save(ProductPrice $productPrice): void {}

    public function get(OrganizationId $organizationId, ProductPriceId $id): ProductPrice
    {
        return $this->effective ?? throw ProductPriceNotFound::withId($id);
    }

    public function find(OrganizationId $organizationId, ProductPriceId $id): ?ProductPrice
    {
        return $this->effective;
    }

    public function findEffective(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
        DateTimeImmutable $businessInstant,
    ): ?ProductPrice {
        return $this->effective;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return null !== $this->effective ? [$this->effective] : [];
    }
}
