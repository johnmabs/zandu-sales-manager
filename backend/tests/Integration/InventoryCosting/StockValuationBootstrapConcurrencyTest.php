<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\InventoryCosting;

use Doctrine\DBAL\{Connection, DriverManager};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\Inventory\Infrastructure\Persistence\Orm\DoctrineStockRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StoreId};

final class StockValuationBootstrapConcurrencyTest extends KernelTestCase
{
    private const ORGANIZATION = '0198f601-1111-7111-8111-111111111111';
    private const STORE = '0198f602-1111-7111-8111-111111111111';
    private const UNIT = '0198f603-1111-7111-8111-111111111111';
    private const PRODUCT = '0198f604-1111-7111-8111-111111111111';
    private const STOCK = '0198f605-1111-7111-8111-111111111111';
    private const ACTOR = '0198f606-1111-7111-8111-111111111111';

    private EntityManagerInterface $entityManager;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
        $this->cleanup();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
        $this->entityManager->clear();
        $this->cleanup();
        parent::tearDown();
    }

    public function testStockRowLockSerializesConcurrentValuationBootstraps(): void
    {
        $uuids = new SymfonyUuidFactory();
        $repository = new DoctrineStockRepository($this->entityManager, $uuids, new BrickDecimalFactory());
        $second = DriverManager::getConnection($this->connection->getParams());

        $this->connection->beginTransaction();
        $this->tenantContext($this->connection);
        $locked = $repository->getForUpdate(
            OrganizationId::fromString(self::ORGANIZATION, $uuids),
            StoreId::fromString(self::STORE, $uuids),
            ProductId::fromString(self::PRODUCT, $uuids),
        );
        self::assertSame(self::STOCK, $locked->id()->toString());

        $second->beginTransaction();
        $this->tenantContext($second);
        $second->executeStatement("SET LOCAL lock_timeout = '100ms'");
        $blocked = false;
        try {
            $second->fetchOne('SELECT id FROM inventory.stock WHERE id = ? FOR UPDATE', [self::STOCK]);
        } catch (Throwable) {
            $blocked = true;
        } finally {
            $second->rollBack();
        }
        self::assertTrue($blocked, 'A concurrent valuation bootstrap must wait for the same Stock row.');
        $this->connection->commit();

        $second->beginTransaction();
        $this->tenantContext($second);
        self::assertSame(self::STOCK, $second->fetchOne('SELECT id FROM inventory.stock WHERE id = ? FOR UPDATE', [self::STOCK]));
        $second->rollBack();
        $second->close();
    }

    private function tenantContext(Connection $connection): void
    {
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->executeStatement("SELECT set_config('app.organization_id', ?, true)", [self::ORGANIZATION]);
    }

    private function fixtures(): void
    {
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,'Costing concurrency','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'LOCK','Lock Store','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION]);
        $this->connection->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,'LOCK-SKU','Locked product','ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [self::PRODUCT, self::ORGANIZATION, self::UNIT, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,10,TRUE,date_trunc('second',NOW()),?,1)", [self::STOCK, self::ORGANIZATION, self::STORE, self::PRODUCT, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $this->connection->executeStatement('DELETE FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM inventory_costing.stock_valuation WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM inventory.stock_movement WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM inventory.stock WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM catalog.products WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM catalog.units_of_measure WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }
}
