<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductActivated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductArchived;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductCreated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductDeactivated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductReactivated;
use Zandu\Modules\Catalog\Domain\Product\Event\ProductUpdated;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductStatus;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final class ProductTest extends TestCase
{
    private const PRODUCT_ID = '0198d2b1-147c-72d5-b75a-a936797ff9c8';
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const BASE_UNIT_ID = '0198d2b2-147c-72d5-b75a-a936797ff9c8';
    private const OTHER_UNIT_ID = '0198d2b3-147c-72d5-b75a-a936797ff9c8';
    private const CATEGORY_ID = '0198d2b4-147c-72d5-b75a-a936797ff9c8';
    private const TAX_CATEGORY_ID = '0198d2b5-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testCreationProducesANormalizedTenantOwnedDraft(): void
    {
        $product = $this->product();
        $events = $product->releaseEvents();

        self::assertSame(self::ORGANIZATION_ID, $product->organizationId()->toString());
        self::assertSame('SKU-001', $product->productCode());
        self::assertSame('Café moulu', $product->name());
        self::assertSame('Paquet de 250 g', $product->description());
        self::assertSame(ProductStatus::Draft, $product->status());
        self::assertTrue($product->inventoryTracked());
        self::assertNull($product->activatedAt());
        self::assertNull($product->updatedAt());
        self::assertSame('UTC', $product->createdAt()->getTimezone()->getName());
        self::assertSame(1, $product->version());
        self::assertInstanceOf(ProductCreated::class, $events[0]);
    }

    public function testServiceCannotBeInventoryTracked(): void
    {
        $this->expectException(LogicException::class);
        $this->product(ProductType::Service, true);
    }

    public function testDraftProfileAllowsCodeAndBaseUnitChanges(): void
    {
        $product = $this->product();
        $product->releaseEvents();

        $product->updateProfile(
            'sku-002',
            'Café premium',
            null,
            ProductType::Physical,
            $this->unitId(self::OTHER_UNIT_ID),
            true,
            $this->taxCategoryId(),
            $this->categoryId(),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T00:00:00+01:00'),
        );

        self::assertSame('SKU-002', $product->productCode());
        self::assertSame(self::OTHER_UNIT_ID, $product->baseUnitId()->toString());
        self::assertNull($product->description());
        self::assertSame('UTC', $product->updatedAt()?->getTimezone()->getName());
        self::assertSame(2, $product->version());
        self::assertInstanceOf(ProductUpdated::class, $product->releaseEvents()[0]);
    }

    public function testFirstActivationRequiresBasePackaging(): void
    {
        $product = $this->product();

        $this->expectException(LogicException::class);
        $product->activate(false, $this->actorId(), new DateTimeImmutable('2026-08-26T00:00:00Z'));
    }

    public function testCodeRemainsImmutableAfterActivationAndDeactivation(): void
    {
        $product = $this->product();
        $product->activate(true, $this->actorId(), new DateTimeImmutable('2026-08-26T00:00:00Z'));
        $product->deactivate($this->actorId(), new DateTimeImmutable('2026-08-26T01:00:00Z'));

        $this->expectException(LogicException::class);
        $product->updateProfile(
            'SKU-CHANGED',
            $product->name(),
            $product->description(),
            $product->type(),
            $product->baseUnitId(),
            $product->inventoryTracked(),
            $product->taxCategoryId(),
            $product->categoryId(),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T02:00:00Z'),
        );
    }

    public function testBaseUnitRemainsImmutableAfterActivation(): void
    {
        $product = $this->product();
        $product->activate(true, $this->actorId(), new DateTimeImmutable('2026-08-26T00:00:00Z'));

        $this->expectException(LogicException::class);
        $product->updateProfile(
            $product->productCode(),
            $product->name(),
            $product->description(),
            $product->type(),
            $this->unitId(self::OTHER_UNIT_ID),
            $product->inventoryTracked(),
            $product->taxCategoryId(),
            $product->categoryId(),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T02:00:00Z'),
        );
    }

    public function testAvailabilityLifecycleIsExplicitAndArchiveIsTerminal(): void
    {
        $product = $this->product();
        $product->releaseEvents();
        $occurredAt = new DateTimeImmutable('2026-08-26T00:00:00Z');

        $product->activate(true, $this->actorId(), $occurredAt);
        self::assertInstanceOf(ProductActivated::class, $product->releaseEvents()[0]);
        self::assertSame(self::ACTOR_ID, $product->activatedBy()?->toString());

        $product->deactivate($this->actorId(), $occurredAt);
        self::assertInstanceOf(ProductDeactivated::class, $product->releaseEvents()[0]);

        try {
            $product->ensureCommerciallyAvailable();
            self::fail('An inactive product must reject new commercial operations.');
        } catch (LogicException) {
        }

        $product->reactivate($this->actorId(), $occurredAt);
        $product->ensureCommerciallyAvailable();
        self::assertInstanceOf(ProductReactivated::class, $product->releaseEvents()[0]);

        $product->archive($this->actorId(), $occurredAt);
        self::assertSame(ProductStatus::Archived, $product->status());
        self::assertSame('Café moulu', $product->name());
        self::assertSame(5, $product->version());
        self::assertInstanceOf(ProductArchived::class, $product->releaseEvents()[0]);

        $this->expectException(LogicException::class);
        $product->reactivate($this->actorId(), $occurredAt);
    }

    private function product(
        ProductType $type = ProductType::Physical,
        bool $inventoryTracked = true,
    ): Product {
        return Product::createDraft(
            ProductId::fromString(self::PRODUCT_ID, new SymfonyUuidFactory()),
            OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory()),
            ' sku-001 ',
            ' Café   moulu ',
            ' Paquet  de 250 g ',
            $type,
            $this->unitId(self::BASE_UNIT_ID),
            $inventoryTracked,
            $this->taxCategoryId(),
            $this->categoryId(),
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T23:00:00+01:00'),
        );
    }

    private function unitId(string $id): UnitOfMeasureId
    {
        return UnitOfMeasureId::fromString($id, new SymfonyUuidFactory());
    }

    private function categoryId(): CategoryId
    {
        return CategoryId::fromString(self::CATEGORY_ID, new SymfonyUuidFactory());
    }

    private function taxCategoryId(): TaxCategoryId
    {
        return TaxCategoryId::fromString(self::TAX_CATEGORY_ID, new SymfonyUuidFactory());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
