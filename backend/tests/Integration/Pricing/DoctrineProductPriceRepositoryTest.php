<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Pricing;

use DateTimeImmutable;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Pricing\Domain\PriceList\PriceList;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListCode;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListName;
use Zandu\Modules\Pricing\Domain\PriceList\PriceListPriority;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceStatus;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceTarget;
use Zandu\Modules\Pricing\Infrastructure\Persistence\Orm\DoctrinePriceListRepository;
use Zandu\Modules\Pricing\Infrastructure\Persistence\Orm\DoctrineProductPriceRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;

final class DoctrineProductPriceRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION = '0198e4a1-b2a4-7b6e-8e0e-608484906502';
    private const UNIT = '0198e4b1-147c-72d5-b75a-a936797ff9c8';
    private const PRODUCT = '0198e4c1-147c-72d5-b75a-a936797ff9c8';
    private const OTHER_PRODUCT = '0198e4c2-147c-72d5-b75a-a936797ff9c8';
    private const PACKAGING = '0198e4d1-147c-72d5-b75a-a936797ff9c8';
    private const PRICE_LIST = '0198e4e1-147c-72d5-b75a-a936797ff9c8';
    private const PRIORITY_PRICE_LIST = '0198e4e2-147c-72d5-b75a-a936797ff9c8';
    private const PRICE_A = '0198e4f1-147c-72d5-b75a-a936797ff9c8';
    private const PRICE_B = '0198e4f2-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    private EntityManagerInterface $em;
    private DoctrinePriceListRepository $priceLists;
    private DoctrineProductPriceRepository $productPrices;
    private DoctrineTenantTransaction $transaction;
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->priceLists = new DoctrinePriceListRepository($this->em, $this->ids);
        $this->productPrices = new DoctrineProductPriceRepository($this->em, $this->ids, $this->decimals);
        $this->transaction = new DoctrineTenantTransaction($this->em->getConnection(), 'zandu_runtime');
        $this->cleanup();
        $this->insertCatalogFixtures();
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        $this->cleanup();
        parent::tearDown();
    }

    public function testProductPriceRoundTripsWithExactAmountAndOptimisticVersion(): void
    {
        $priceList = $this->priceList();
        $productPrice = $this->productPrice(self::PRICE_A, $priceList);
        $organizationId = $this->organizationId();
        $this->transaction->transactional($organizationId, function () use ($priceList, $productPrice): void {
            $this->priceLists->save($priceList);
            $this->productPrices->save($productPrice);
        });

        $productPrice->deactivate($this->actorId(), new DateTimeImmutable('2026-08-26T12:00:00Z'));
        $this->transaction->transactional($organizationId, fn() => $this->productPrices->save($productPrice));
        $this->em->clear();

        $restored = $this->transaction->transactional(
            $organizationId,
            fn(): ProductPrice => $this->productPrices->get($organizationId, $productPrice->id()),
        );
        self::assertSame('1500.000000000000', $restored->amount()->amount()->toString());
        self::assertSame('XAF', $restored->amount()->currency()->code());
        self::assertSame(ProductPriceStatus::Inactive, $restored->status());
        self::assertSame(self::PRODUCT, $restored->productId()->toString());
        self::assertSame(self::PACKAGING, $restored->packagingId()->toString());
        self::assertSame(2, $restored->version());
    }

    public function testActivePeriodsCannotOverlapForTheSameTargetAndList(): void
    {
        $priceList = $this->priceList();
        $organizationId = $this->organizationId();
        $this->transaction->transactional($organizationId, function () use ($priceList): void {
            $this->priceLists->save($priceList);
            $this->productPrices->save($this->productPrice(
                self::PRICE_A,
                $priceList,
                new DateTimeImmutable('2026-08-26T10:00:00Z'),
                new DateTimeImmutable('2026-08-26T12:00:00Z'),
            ));
        });
        $this->em->clear();

        $this->expectException(DbalException::class);
        $this->transaction->transactional($organizationId, fn() => $this->productPrices->save($this->productPrice(
            self::PRICE_B,
            $priceList,
            new DateTimeImmutable('2026-08-26T12:00:00Z'),
            new DateTimeImmutable('2026-08-26T14:00:00Z'),
        )));
    }

    public function testPackagingMustBelongToThePersistedProduct(): void
    {
        $priceList = $this->priceList();
        $organizationId = $this->organizationId();
        $this->transaction->transactional($organizationId, fn() => $this->priceLists->save($priceList));
        $wrongTarget = new ProductPriceTarget(
            $organizationId,
            ProductId::fromString(self::OTHER_PRODUCT, $this->ids),
            ProductPackagingId::fromString(self::PACKAGING, $this->ids),
        );
        $productPrice = ProductPrice::createActive(
            ProductPriceId::fromString(self::PRICE_A, $this->ids),
            $priceList,
            $wrongTarget,
            Money::fromString('1500', Currency::fromCode('XAF'), $this->decimals),
            null,
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T09:00:00Z'),
        );

        $this->expectException(DbalException::class);
        $this->transaction->transactional($organizationId, fn() => $this->productPrices->save($productPrice));
    }

    public function testEffectivePriceSelectionUsesHighestListPriorityDeterministically(): void
    {
        $organizationId = $this->organizationId();
        $standard = $this->priceList();
        $priority = $this->priceList(self::PRIORITY_PRICE_LIST, 'PRIORITY', 10);
        $standard->activate($this->actorId(), new DateTimeImmutable('2026-08-26T08:30:00Z'));
        $priority->activate($this->actorId(), new DateTimeImmutable('2026-08-26T08:30:00Z'));
        $this->transaction->transactional($organizationId, function () use ($standard, $priority): void {
            $this->priceLists->save($standard);
            $this->priceLists->save($priority);
            $this->productPrices->save($this->productPrice(self::PRICE_A, $standard));
            $this->productPrices->save($this->productPrice(self::PRICE_B, $priority));
        });
        $this->em->clear();

        $effective = $this->transaction->transactional(
            $organizationId,
            fn(): ?ProductPrice => $this->productPrices->findEffective(
                $organizationId,
                ProductId::fromString(self::PRODUCT, $this->ids),
                ProductPackagingId::fromString(self::PACKAGING, $this->ids),
                new DateTimeImmutable('2026-08-26T10:00:00Z'),
            ),
        );

        self::assertNotNull($effective);
        self::assertSame(self::PRICE_B, $effective->id()->toString());
        self::assertSame(self::PRIORITY_PRICE_LIST, $effective->priceListId()->toString());
    }

    private function priceList(
        string $id = self::PRICE_LIST,
        string $code = 'RETAIL',
        int $priority = 0,
    ): PriceList {
        return PriceList::createDraft(
            PriceListId::fromString($id, $this->ids),
            $this->organizationId(),
            PriceListCode::fromString($code),
            PriceListName::fromString('Tarif standard'),
            Currency::fromCode('XAF'),
            null,
            null,
            PriceListPriority::fromInt($priority),
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T08:00:00Z'),
        );
    }

    private function productPrice(
        string $id,
        PriceList $priceList,
        ?DateTimeImmutable $validFrom = null,
        ?DateTimeImmutable $validTo = null,
    ): ProductPrice {
        return ProductPrice::createActive(
            ProductPriceId::fromString($id, $this->ids),
            $priceList,
            new ProductPriceTarget(
                $this->organizationId(),
                ProductId::fromString(self::PRODUCT, $this->ids),
                ProductPackagingId::fromString(self::PACKAGING, $this->ids),
            ),
            Money::fromString('1500', Currency::fromCode('XAF'), $this->decimals),
            $validFrom,
            $validTo,
            $this->actorId(),
            new DateTimeImmutable('2026-08-26T09:00:00Z'),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION, $this->ids);
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR, $this->ids);
    }

    private function insertCatalogFixtures(): void
    {
        $db = $this->em->getConnection();
        $db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Product price tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION]);
        $db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,version) VALUES (?,?,'SKU','Produit','DRAFT','PHYSICAL',?,TRUE,NOW(),?,1)", [self::PRODUCT, self::ORGANIZATION, self::UNIT, self::ACTOR]);
        $db->executeStatement("INSERT INTO catalog.product_packagings (id,organization_id,product_id,base,code,name,unit_id,conversion_factor,precision,minimum_quantity,quantity_increment,allowed_for_sale,allowed_for_purchase,status,created_at,created_by,version) VALUES (?,?,?,TRUE,'EA','Article',?,1,0,1,1,TRUE,TRUE,'ACTIVE',NOW(),?,1)", [self::PACKAGING, self::ORGANIZATION, self::PRODUCT, self::UNIT, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $db = $this->em->getConnection();
        $db->executeStatement('DELETE FROM pricing.product_prices WHERE organization_id = ?', [self::ORGANIZATION]);
        $db->executeStatement('DELETE FROM pricing.price_lists WHERE organization_id = ?', [self::ORGANIZATION]);
        $db->executeStatement('DELETE FROM catalog.product_packagings WHERE organization_id = ?', [self::ORGANIZATION]);
        $db->executeStatement('DELETE FROM catalog.products WHERE organization_id = ?', [self::ORGANIZATION]);
        $db->executeStatement('DELETE FROM catalog.units_of_measure WHERE organization_id = ?', [self::ORGANIZATION]);
        $db->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }
}
