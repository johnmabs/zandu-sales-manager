<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductCode;
use Zandu\Modules\Catalog\Domain\Product\ProductName;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ConversionFactor;
use Zandu\Modules\Catalog\Domain\ProductPackaging\IncompatiblePackagingQuantity;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingCode;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingName;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingPrecision;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingQuantityConverter;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingStatus;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Quantity\Quantity;

final class ProductPackagingTest extends TestCase
{
    private const PACKAGING_ID = '0198d2c1-147c-72d5-b75a-a936797ff9c8';
    private const ORGANIZATION_ID = '0198d2c2-b2a4-7b6e-8e0e-608484906502';
    private const PRODUCT_ID = '0198d2c3-147c-72d5-b75a-a936797ff9c8';
    private const UNIT_ID = '0198d2c4-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItCreatesAnActiveTenantOwnedPackagingWithExactDecimals(): void
    {
        $packaging = $this->packaging();

        self::assertSame(self::PACKAGING_ID, $packaging->id()->toString());
        self::assertSame(self::ORGANIZATION_ID, $packaging->organizationId()->toString());
        self::assertSame(self::PRODUCT_ID, $packaging->productId()->toString());
        self::assertFalse($packaging->isBase());
        self::assertSame('CARTON-24', $packaging->code()->value());
        self::assertSame('Carton de 24', $packaging->name()->value());
        self::assertSame('24.000000000001', $packaging->conversionFactor()->toString());
        self::assertSame('0.25', $packaging->minimumQuantity()->toString());
        self::assertSame(ProductPackagingStatus::Active, $packaging->status());
        self::assertSame(1, $packaging->version());
    }

    #[DataProvider('invalidPositiveDecimals')]
    public function testConversionFactorMustBeStrictlyPositive(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ConversionFactor($this->decimals->fromString($value));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPositiveDecimals(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-0.01'];
    }

    public function testConversionFactorCannotExceedAdrScale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('12 decimal places');

        new ConversionFactor($this->decimals->fromString('1.1234567890123'));
    }

    public function testQuantitiesMustBePositiveAndCompatibleWithPackagingPrecision(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds product packaging precision');

        $this->packaging(minimumQuantity: '0.001', precision: 2);
    }

    public function testCommercialSettingsCanChangeWithoutReinterpretingConversion(): void
    {
        $packaging = $this->packaging();
        $factor = $packaging->conversionFactor();
        $unitId = $packaging->unitId();

        $packaging->updateCommercialSettings(
            ProductPackagingName::fromString('Carton promotionnel'),
            $this->quantity('0.50'),
            $this->quantity('0.25'),
            false,
            true,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T19:00:00+01:00'),
        );

        self::assertSame('Carton promotionnel', $packaging->name()->value());
        self::assertFalse($packaging->allowedForSale());
        self::assertTrue($packaging->allowedForPurchase());
        self::assertSame('2026-08-25T18:00:00+00:00', $packaging->updatedAt()?->format('c'));
        self::assertTrue($factor->equals($packaging->conversionFactor()));
        self::assertTrue($unitId->equals($packaging->unitId()));
        self::assertSame(2, $packaging->version());
    }

    public function testAvailabilityLifecycleEndsWithArchive(): void
    {
        $packaging = $this->packaging();
        $packaging->deactivate($this->actorId(), new DateTimeImmutable('2026-08-25T19:00:00Z'));
        self::assertSame(ProductPackagingStatus::Inactive, $packaging->status());
        $packaging->activate($this->actorId(), new DateTimeImmutable('2026-08-25T20:00:00Z'));
        self::assertSame(ProductPackagingStatus::Active, $packaging->status());
        $packaging->archive($this->actorId(), new DateTimeImmutable('2026-08-25T21:00:00Z'));
        self::assertSame(ProductPackagingStatus::Archived, $packaging->status());
        self::assertSame(4, $packaging->version());

        $this->expectException(LogicException::class);
        $packaging->activate($this->actorId(), new DateTimeImmutable('2026-08-25T22:00:00Z'));
    }

    public function testSaleAndPurchaseAvailabilityAreIndependent(): void
    {
        $packaging = $this->packaging(allowedForSale: true, allowedForPurchase: false);
        $packaging->ensureAvailableForSale();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('not allowed for purchase');
        $packaging->ensureAvailableForPurchase();
    }

    public function testCodeNameAndPrecisionNormalizeAndValidateInputs(): void
    {
        self::assertSame('PACK 6', ProductPackagingCode::fromString(' pack 6 ')->value());
        self::assertSame('Pack de 6', ProductPackagingName::fromString(" Pack  de\n6 ")->value());
        self::assertSame(12, ProductPackagingPrecision::fromInt(12)->value());

        $this->expectException(InvalidArgumentException::class);
        ProductPackagingPrecision::fromInt(13);
    }

    public function testBasePackagingDerivesOwnershipAndUnitFromProduct(): void
    {
        $product = $this->product();
        $packaging = ProductPackaging::createBase(
            ProductPackagingId::fromString(self::PACKAGING_ID, new SymfonyUuidFactory()),
            $product,
            ProductPackagingCode::fromString('EA'),
            ProductPackagingName::fromString('Article'),
            new ConversionFactor($this->decimals->fromString('1.000')),
            ProductPackagingPrecision::fromInt(2),
            $this->quantity('0.25'),
            $this->quantity('0.25'),
            true,
            true,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T17:00:00Z'),
        );

        self::assertTrue($packaging->isBase());
        self::assertTrue($packaging->organizationId()->equals($product->organizationId()));
        self::assertTrue($packaging->productId()->equals($product->id()));
        self::assertTrue($packaging->unitId()->equals($product->baseUnitId()));
        self::assertSame('1.000', $packaging->conversionFactor()->toString());
    }

    public function testBasePackagingRequiresAConversionFactorOfExactlyOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must equal one');

        ProductPackaging::createBase(
            ProductPackagingId::fromString(self::PACKAGING_ID, new SymfonyUuidFactory()),
            $this->product(),
            ProductPackagingCode::fromString('PACK-6'),
            ProductPackagingName::fromString('Pack de 6'),
            new ConversionFactor($this->decimals->fromString('6')),
            ProductPackagingPrecision::fromInt(0),
            $this->quantity('1'),
            $this->quantity('1'),
            true,
            true,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T17:00:00Z'),
        );
    }

    public function testItConvertsIntegerAndDecimalQuantitiesExactly(): void
    {
        $converter = new ProductPackagingQuantityConverter();

        self::assertSame('48', $converter->toBaseQuantity(
            $this->quantity('2'),
            $this->packaging(conversionFactor: '24', minimumQuantity: '1', quantityIncrement: '1', precision: 0),
            0,
        )->toString());
        self::assertSame('1.500', $converter->toBaseQuantity(
            $this->quantity('1.2'),
            $this->packaging(conversionFactor: '1.25', minimumQuantity: '0.1', quantityIncrement: '0.1', precision: 1),
            2,
        )->toString());
    }

    public function testItSupportsTheMaximumDocumentedPrecisionWithoutRounding(): void
    {
        $quantity = (new ProductPackagingQuantityConverter())->toBaseQuantity(
            $this->quantity('0.123456789012'),
            $this->packaging(
                conversionFactor: '1',
                minimumQuantity: '0.000000000001',
                quantityIncrement: '0.000000000001',
                precision: 12,
            ),
            12,
        );

        self::assertSame('0.123456789012', $quantity->toString());
    }

    #[DataProvider('incompatibleQuantityCases')]
    public function testItRejectsIncompatibleQuantities(
        string $entered,
        string $factor,
        string $minimum,
        string $increment,
        int $packagingPrecision,
        int $basePrecision,
        string $message,
    ): void {
        $this->expectException(IncompatiblePackagingQuantity::class);
        $this->expectExceptionMessage($message);

        (new ProductPackagingQuantityConverter())->toBaseQuantity(
            $this->quantity($entered),
            $this->packaging(
                conversionFactor: $factor,
                minimumQuantity: $minimum,
                quantityIncrement: $increment,
                precision: $packagingPrecision,
            ),
            $basePrecision,
        );
    }

    /** @return iterable<string, array{string, string, string, string, int, int, string}> */
    public static function incompatibleQuantityCases(): iterable
    {
        yield 'not positive' => ['0', '1', '0.1', '0.1', 1, 1, 'greater than zero'];
        yield 'packaging precision' => ['1.01', '1', '0.1', '0.1', 1, 2, 'packaging precision'];
        yield 'below minimum' => ['0.5', '1', '1', '0.5', 1, 1, 'below the minimum'];
        yield 'increment residue' => ['1.1', '1', '0.1', '0.25', 2, 2, 'exact multiple'];
        yield 'base precision residue' => ['0.3', '0.5', '0.1', '0.1', 1, 1, 'base unit precision'];
    }

    private function packaging(
        string $conversionFactor = '24.000000000001',
        string $minimumQuantity = '0.25',
        string $quantityIncrement = '0.25',
        int $precision = 2,
        bool $allowedForSale = true,
        bool $allowedForPurchase = true,
    ): ProductPackaging {
        $factory = new SymfonyUuidFactory();

        return ProductPackaging::createAdditional(
            ProductPackagingId::fromString(self::PACKAGING_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            ProductId::fromString(self::PRODUCT_ID, $factory),
            ProductPackagingCode::fromString(' carton-24 '),
            ProductPackagingName::fromString(' Carton  de 24 '),
            UnitOfMeasureId::fromString(self::UNIT_ID, $factory),
            new ConversionFactor($this->decimals->fromString($conversionFactor)),
            ProductPackagingPrecision::fromInt($precision),
            $this->quantity($minimumQuantity),
            $this->quantity($quantityIncrement),
            $allowedForSale,
            $allowedForPurchase,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T17:00:00Z'),
        );
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }

    private function product(): Product
    {
        $factory = new SymfonyUuidFactory();

        return Product::createDraft(
            ProductId::fromString(self::PRODUCT_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            ProductCode::fromString('SKU-001'),
            ProductName::fromString('Café'),
            null,
            ProductType::Physical,
            UnitOfMeasureId::fromString(self::UNIT_ID, $factory),
            true,
            null,
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T16:00:00Z'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
