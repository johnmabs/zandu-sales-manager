<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Inventory;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountMode, StockCountRepository, StockCountScopeType};
use Zandu\Modules\Inventory\Infrastructure\Persistence\DbalStockCountRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StoreId};

final class DbalStockCountRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION = '0199f900-0000-7000-8000-000000000001';
    private const OTHER_ORGANIZATION = '0199f900-0000-7000-8000-000000000002';
    private const STORE = '0199f900-0000-7000-8000-000000000003';
    private const PRODUCT = '0199f900-0000-7000-8000-000000000004';
    private const COUNT = '0199f900-0000-7000-8000-000000000005';
    private const ACTOR = '0199f900-0000-7000-8000-000000000006';

    private Connection $db;
    private SymfonyUuidFactory $ids;
    private StockCountRepository $repository;
    private DoctrineTenantTransaction $transactions;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->ids = new SymfonyUuidFactory();
        $this->repository = new DbalStockCountRepository($this->db, $this->ids);
        $this->transactions = new DoctrineTenantTransaction($this->db, 'zandu_runtime');
        $this->cleanup();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testPartialDraftRoundTripsAndRlsHidesItFromAnotherTenant(): void
    {
        $organizationId = OrganizationId::fromString(self::ORGANIZATION, $this->ids);
        $count = StockCount::create(
            StockCountId::fromString(self::COUNT, $this->ids),
            $organizationId,
            StoreId::fromString(self::STORE, $this->ids),
            StockCountScopeType::Partial,
            ActorId::fromString(self::ACTOR, $this->ids),
            new DateTimeImmutable('2026-08-31T19:00:00Z'),
            StockCountMode::Guided,
            [ProductId::fromString(self::PRODUCT, $this->ids)],
        );

        $restored = $this->transactions->transactional($organizationId, function () use ($count): StockCount {
            $this->repository->save($count);

            return $this->repository->get($count->organizationId(), $count->id());
        });
        self::assertSame(StockCountMode::Guided, $restored->mode());
        self::assertSame(self::PRODUCT, $restored->requestedProductIds()[0]->toString());
        self::assertNull($this->transactions->transactional(OrganizationId::fromString(self::OTHER_ORGANIZATION, $this->ids), fn() => $this->repository->find(OrganizationId::fromString(self::OTHER_ORGANIZATION, $this->ids), $count->id())));
    }

    private function fixtures(): void
    {
        $this->db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,'Stock count tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'COUNT','Count store','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, self::ACTOR, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $this->db->executeStatement('DELETE FROM inventory.stock_count WHERE organization_id=?', [self::ORGANIZATION]);
        $this->db->executeStatement('DELETE FROM organization.stores WHERE organization_id=?', [self::ORGANIZATION]);
        $this->db->executeStatement('DELETE FROM organization.organizations WHERE id=?', [self::ORGANIZATION]);
    }
}
