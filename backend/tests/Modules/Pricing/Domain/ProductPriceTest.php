<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Pricing\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceActivated;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceArchived;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceCreated;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceDeactivated;
use Zandu\Modules\Pricing\Domain\ProductPrice\Event\ProductPriceUpdated;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceStatus;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceTarget;
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

final class ProductPriceTest extends TestCase
{
    private const ORGANIZATION_ID = '0198e3a1-b2a4-7b6e-8e0e-608484906502';
    private const OTHER_ORGANIZATION_ID = '0198e3a2-b2a4-7b6e-8e0e-608484906502';
    private const PRICE_LIST_ID = '0198e3b1-147c-72d5-b75a-a936797ff9c8';
    private const PRODUCT_PRICE_ID = '0198e3c1-147c-72d5-b75a-a936797ff9c8';
    private const PRODUCT_ID = '0198e3d1-147c-72d5-b75a-a936797ff9c8';
    private const PACKAGING_ID = '0198e3e1-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testCreationDerivesImmutableOwnershipAndCurrencyFromPriceListAndTarget(): void
    {
        $productPrice = $this->productPrice();

        self::assertSame(self::ORGANIZATION_ID, $productPrice->organizationId()->toString());
        self::assertSame(self::PRICE_LIST_ID, $productPrice->priceListId()->toString());
        self::assertSame(self::PRODUCT_ID, $productPrice->productId()->toString());
        self::assertSame(self::PACKAGING_ID, $productPrice->packagingId()->toString());
        self::assertSame('1500.00', $productPrice->amount()->amount()->toString());
        self::assertSame('XAF', $productPrice->amount()->currency()->code());
        self::assertSame(ProductPriceStatus::Active, $productPrice->status());
        self::assertSame('UTC', $productPrice->validFrom()?->getTimezone()->getName());
        self::assertSame(1, $productPrice->version());
        self::assertInstanceOf(ProductPriceCreated::class, $productPrice->releaseEvents()[0]);
    }

    public function testTargetMustBelongToPriceListTenant(): void
    {
        $target = new ProductPriceTarget(
            $this->organizationId(self::OTHER_ORGANIZATION_ID),
            ProductId::fromString(self::PRODUCT_ID, $this->ids),
            ProductPackagingId::fromString(self::PACKAGING_ID, $this->ids),
        );

        $this->expectException(LogicException::class);
        $this->create($this->priceList(), $target, $this->money('1500', 'XAF'));
    }

    public function testCurrencyMustMatchPriceList(): void
    {
        $this->expectException(LogicException::class);
        $this->create($this->priceList(), $this->target(), $this->money('10', 'EUR'));
    }

    public function testAmountMustBeNonNegativeAndZeroIsAllowed(): void
    {
        self::assertSame('0', $this->create($this->priceList(), $this->target(), $this->money('0', 'XAF'))->amount()->amount()->toString());

        $this->expectException(InvalidArgumentException::class);
        $this->create($this->priceList(), $this->target(), $this->money('-0.01', 'XAF'));
    }

    public function testValidityEndCannotPrecedeStart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->create(
            $this->priceList(),
            $this->target(),
            $this->money('1500', 'XAF'),
            new DateTimeImmutable('2026-08-26T12:00:00Z'),
            new DateTimeImmutable('2026-08-26T11:59:59Z'),
        );
    }

    public function testUpdateRetainsPriceListAndProducesAnEvent(): void
    {
        $productPrice = $this->productPrice();
        $productPrice->releaseEvents();
        $productPrice->update(
            $this->priceList(),
            $this->money('1750', 'XAF'),
            null,
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T12:00:00+01:00'),
        );

        self::assertSame('1750', $productPrice->amount()->amount()->toString());
        self::assertNull($productPrice->validFrom());
        self::assertSame(2, $productPrice->version());
        self::assertInstanceOf(ProductPriceUpdated::class, $productPrice->releaseEvents()[0]);
    }

    public function testLifecycleAndEffectivePeriodAreExplicitAndArchiveIsTerminal(): void
    {
        $productPrice = $this->productPrice();
        $productPrice->releaseEvents();
        self::assertFalse($productPrice->isEffectiveAt(new DateTimeImmutable('2026-08-26T09:59:59Z')));
        self::assertTrue($productPrice->isEffectiveAt(new DateTimeImmutable('2026-08-26T10:00:00Z')));

        $productPrice->deactivate($this->actorId(), new DateTimeImmutable('2026-08-26T11:00:00Z'));
        self::assertInstanceOf(ProductPriceDeactivated::class, $productPrice->releaseEvents()[0]);
        self::assertFalse($productPrice->isEffectiveAt(new DateTimeImmutable('2026-08-26T10:30:00Z')));
        $productPrice->activate($this->actorId(), new DateTimeImmutable('2026-08-26T12:00:00Z'));
        self::assertInstanceOf(ProductPriceActivated::class, $productPrice->releaseEvents()[0]);
        $productPrice->archive($this->actorId(), new DateTimeImmutable('2026-08-26T13:00:00Z'));
        self::assertInstanceOf(ProductPriceArchived::class, $productPrice->releaseEvents()[0]);
        self::assertSame(4, $productPrice->version());
        self::assertFalse($productPrice->isEffectiveAt(new DateTimeImmutable('2026-08-26T10:30:00Z')));

        $this->expectException(LogicException::class);
        $productPrice->activate($this->actorId(), new DateTimeImmutable());
    }

    private function productPrice(): ProductPrice
    {
        return $this->create($this->priceList(), $this->target(), $this->money('1500.00', 'XAF'));
    }

    private function create(
        PriceList $priceList,
        ProductPriceTarget $target,
        Money $amount,
        ?DateTimeImmutable $validFrom = new DateTimeImmutable('2026-08-26T11:00:00+01:00'),
        ?DateTimeImmutable $validTo = null,
    ): ProductPrice {
        return ProductPrice::createActive(
            ProductPriceId::fromString(self::PRODUCT_PRICE_ID, $this->ids),
            $priceList,
            $target,
            $amount,
            $validFrom,
            $validTo,
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T09:00:00Z'),
        );
    }

    private function priceList(): PriceList
    {
        return PriceList::createDraft(
            PriceListId::fromString(self::PRICE_LIST_ID, $this->ids),
            $this->organizationId(self::ORGANIZATION_ID),
            PriceListCode::fromString('RETAIL'),
            PriceListName::fromString('Tarif standard'),
            Currency::fromCode('XAF'),
            null,
            null,
            PriceListPriority::fromInt(0),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T08:00:00Z'),
        );
    }

    private function target(): ProductPriceTarget
    {
        return new ProductPriceTarget(
            $this->organizationId(self::ORGANIZATION_ID),
            ProductId::fromString(self::PRODUCT_ID, $this->ids),
            ProductPackagingId::fromString(self::PACKAGING_ID, $this->ids),
        );
    }

    private function money(string $amount, string $currency): Money
    {
        return Money::fromString($amount, Currency::fromCode($currency), $this->decimals);
    }

    private function organizationId(string $id): OrganizationId
    {
        return OrganizationId::fromString($id, $this->ids);
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, $this->ids);
    }
}
