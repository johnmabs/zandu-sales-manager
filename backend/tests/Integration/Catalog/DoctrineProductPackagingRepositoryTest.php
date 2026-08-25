<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Catalog;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductCode;
use Zandu\Modules\Catalog\Domain\Product\ProductName;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ConversionFactor;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingCode;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingName;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingPrecision;
use Zandu\Modules\Catalog\Infrastructure\Persistence\Orm\DoctrineProductPackagingRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Quantity\Quantity;

final class DoctrineProductPackagingRepositoryTest extends KernelTestCase
{
    private const ORG = '0198d2d1-b2a4-7b6e-8e0e-608484906502';
    private const UNIT = '0198d2d2-147c-72d5-b75a-a936797ff9c8';
    private const PRODUCT = '0198d2d3-147c-72d5-b75a-a936797ff9c8';
    private const PACKAGING = '0198d2d4-147c-72d5-b75a-a936797ff9c8';
    private const OTHER = '0198d2d5-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    private EntityManagerInterface $em;
    private DoctrineProductPackagingRepository $repository;
    private DoctrineTenantTransaction $transaction;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->decimals = new BrickDecimalFactory();
        $this->repository = new DoctrineProductPackagingRepository($this->em, new SymfonyUuidFactory(), $this->decimals);
        $this->transaction = new DoctrineTenantTransaction($this->em->getConnection(), 'zandu_runtime');
        $this->cleanup();
        $db = $this->em->getConnection();
        $db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Packaging tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORG, self::ACTOR, self::ACTOR]);
        $db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORG]);
        $db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,version) VALUES (?,?,'SKU','Produit','DRAFT','PHYSICAL',?,TRUE,NOW(),?,1)", [self::PRODUCT, self::ORG, self::UNIT, self::ACTOR]);
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        $this->cleanup();
        parent::tearDown();
    }

    public function testBasePackagingRoundTripsAndActivatesPresenceContract(): void
    {
        $packaging = $this->base(self::PACKAGING);
        $org = $packaging->organizationId();
        $this->transaction->transactional($org, fn() => $this->repository->save($packaging));
        $this->em->clear();

        $restored = $this->transaction->transactional($org, fn(): ProductPackaging => $this->repository->get($org, $packaging->id()));
        self::assertTrue($restored->isBase());
        self::assertSame('1.000000000000', $restored->conversionFactor()->toString());
        self::assertTrue($this->transaction->transactional($org, fn(): bool => $this->repository->exists($org, $packaging->productId(), $packaging->unitId())));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->transaction->transactional($org, fn() => $this->repository->save($this->base(self::OTHER)));
    }

    private function base(string $id): ProductPackaging
    {
        $f = new SymfonyUuidFactory();
        $actor = ActorId::fromString(self::ACTOR, $f);
        $product = Product::createDraft(ProductId::fromString(self::PRODUCT, $f), OrganizationId::fromString(self::ORG, $f), ProductCode::fromString('SKU'), ProductName::fromString('Produit'), null, ProductType::Physical, UnitOfMeasureId::fromString(self::UNIT, $f), true, null, null, $actor, new DateTimeImmutable('2026-08-25T10:00:00Z'));
        return ProductPackaging::createBase(ProductPackagingId::fromString($id, $f), $product, ProductPackagingCode::fromString('EA-' . $id), ProductPackagingName::fromString('Article'), new ConversionFactor($this->decimals->fromString('1')), ProductPackagingPrecision::fromInt(0), Quantity::fromString('1', $this->decimals), Quantity::fromString('1', $this->decimals), true, true, $actor, new DateTimeImmutable('2026-08-25T11:00:00Z'));
    }

    private function cleanup(): void
    {
        $db = $this->em->getConnection();
        $db->executeStatement('DELETE FROM catalog.product_packagings WHERE organization_id = ?', [self::ORG]);
        $db->executeStatement('DELETE FROM catalog.products WHERE organization_id = ?', [self::ORG]);
        $db->executeStatement('DELETE FROM catalog.units_of_measure WHERE organization_id = ?', [self::ORG]);
        $db->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORG]);
    }
}
