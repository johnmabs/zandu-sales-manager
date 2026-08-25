<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\RepositoryBarcodeResolver;
use Zandu\Modules\Catalog\Domain\ProductBarcode\Barcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeRepository;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ConversionFactor;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingCode;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingName;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingPrecision;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductBarcodeId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Quantity\Quantity;

final class BarcodeResolverTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private OrganizationId $organizationId;
    private ActorId $actorId;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->organizationId = OrganizationId::fromString('0198d2e1-b2a4-7b6e-8e0e-608484906502', $this->ids);
        $this->actorId = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $this->ids);
    }

    public function testItResolvesAnActiveBarcodeWithinItsTenant(): void
    {
        $productBarcode = $this->barcode();
        $resolver = new RepositoryBarcodeResolver(new InMemoryProductBarcodeRepository([$productBarcode]));

        $resolution = $resolver->resolve($this->organizationId, Barcode::fromString(' 0012 ab '));

        self::assertNotNull($resolution);
        self::assertTrue($resolution->productId()->equals($productBarcode->productId()));
        self::assertTrue($resolution->packagingId()->equals($productBarcode->packagingId()));
    }

    public function testItReturnsNotFoundForAnotherTenant(): void
    {
        $resolver = new RepositoryBarcodeResolver(new InMemoryProductBarcodeRepository([$this->barcode()]));
        $otherTenant = OrganizationId::fromString('0198d2e1-b2a4-7b6e-8e0e-608484906503', $this->ids);

        self::assertNull($resolver->resolve($otherTenant, Barcode::fromString('0012AB')));
    }

    public function testItReturnsNotFoundForARemovedBarcode(): void
    {
        $productBarcode = $this->barcode();
        $productBarcode->remove($this->actorId, new DateTimeImmutable('2026-08-25T11:00:00Z'));
        $resolver = new RepositoryBarcodeResolver(new InMemoryProductBarcodeRepository([$productBarcode]));

        self::assertNull($resolver->resolve($this->organizationId, Barcode::fromString('0012AB')));
    }

    private function barcode(): ProductBarcode
    {
        $decimals = new BrickDecimalFactory();
        $packaging = ProductPackaging::createAdditional(
            ProductPackagingId::fromString('0198d2e4-147c-72d5-b75a-a936797ff9c8', $this->ids),
            $this->organizationId,
            ProductId::fromString('0198d2e2-147c-72d5-b75a-a936797ff9c8', $this->ids),
            ProductPackagingCode::fromString('EA'),
            ProductPackagingName::fromString('Article'),
            UnitOfMeasureId::fromString('0198d2e3-147c-72d5-b75a-a936797ff9c8', $this->ids),
            new ConversionFactor($decimals->fromString('1')),
            ProductPackagingPrecision::fromInt(0),
            Quantity::fromString('1', $decimals),
            Quantity::fromString('1', $decimals),
            true,
            true,
            $this->actorId,
            new DateTimeImmutable('2026-08-25T09:00:00Z'),
        );

        return ProductBarcode::add(
            ProductBarcodeId::fromString('0198d2e5-147c-72d5-b75a-a936797ff9c8', $this->ids),
            $packaging,
            Barcode::fromString('0012 ab'),
            $this->actorId,
            new DateTimeImmutable('2026-08-25T10:00:00Z'),
        );
    }
}

final class InMemoryProductBarcodeRepository implements ProductBarcodeRepository
{
    /** @param list<ProductBarcode> $barcodes */
    public function __construct(private array $barcodes) {}

    public function save(ProductBarcode $barcode): void
    {
        $this->barcodes[] = $barcode;
    }

    public function findByBarcode(OrganizationId $organizationId, Barcode $barcode): ?ProductBarcode
    {
        foreach ($this->barcodes as $candidate) {
            if ($candidate->organizationId()->equals($organizationId)
                && $candidate->barcode()->normalized() === $barcode->normalized()) {
                return $candidate;
            }
        }

        return null;
    }
}
