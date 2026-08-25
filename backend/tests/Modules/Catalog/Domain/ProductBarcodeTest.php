<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Domain\ProductBarcode\Barcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcode;
use Zandu\Modules\Catalog\Domain\ProductBarcode\ProductBarcodeStatus;
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

final class ProductBarcodeTest extends TestCase
{
    public function testBarcodeRemainsAStringAndNormalizesForComparison(): void
    {
        $b = Barcode::fromString(' 0012 ab  ');
        self::assertSame('0012 ab', $b->raw());
        self::assertSame('0012AB', $b->normalized());
    }
    public function testOwnershipComesFromPackagingAndRemovalIsTerminal(): void
    {
        $f = new SymfonyUuidFactory();
        $a = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $f);
        $p = $this->packaging();
        $b = ProductBarcode::add(ProductBarcodeId::fromString('0198d2e5-147c-72d5-b75a-a936797ff9c8', $f), $p, Barcode::fromString('0012345678905'), $a, new DateTimeImmutable('2026-08-25T10:00:00Z'));
        self::assertTrue($b->organizationId()->equals($p->organizationId()));
        self::assertTrue($b->productId()->equals($p->productId()));
        self::assertTrue($b->packagingId()->equals($p->id()));
        $b->remove($a, new DateTimeImmutable('2026-08-25T11:00:00Z'));
        self::assertSame(ProductBarcodeStatus::Removed, $b->status());
        self::assertSame(2, $b->version());
        $this->expectException(LogicException::class);
        $b->remove($a, new DateTimeImmutable());
    }
    private function packaging(): ProductPackaging
    {
        $f = new SymfonyUuidFactory();
        $d = new BrickDecimalFactory();
        $a = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $f);
        return ProductPackaging::createAdditional(ProductPackagingId::fromString('0198d2e4-147c-72d5-b75a-a936797ff9c8', $f), OrganizationId::fromString('0198d2e1-b2a4-7b6e-8e0e-608484906502', $f), ProductId::fromString('0198d2e2-147c-72d5-b75a-a936797ff9c8', $f), ProductPackagingCode::fromString('EA'), ProductPackagingName::fromString('Article'), UnitOfMeasureId::fromString('0198d2e3-147c-72d5-b75a-a936797ff9c8', $f), new ConversionFactor($d->fromString('1')), ProductPackagingPrecision::fromInt(0), Quantity::fromString('1', $d), Quantity::fromString('1',$d), true, true, $a, new DateTimeImmutable('2026-08-25T09:00:00Z'));
    }
}
