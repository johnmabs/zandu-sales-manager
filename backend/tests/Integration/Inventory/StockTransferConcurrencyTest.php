<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Inventory;

use Doctrine\DBAL\{Connection, DriverManager};
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

final class StockTransferConcurrencyTest extends KernelTestCase
{
    private const ORGANIZATION = '0199f400-0000-7000-8000-000000000001';
    private const SOURCE_STORE = '0199f400-0000-7000-8000-000000000002';
    private const DESTINATION_STORE = '0199f400-0000-7000-8000-000000000003';
    private const UNIT = '0199f400-0000-7000-8000-000000000004';
    private const PRODUCT = '0199f400-0000-7000-8000-000000000005';
    private const STOCK = '0199f400-0000-7000-8000-000000000006';
    private const TRANSFER_A = '0199f400-0000-7000-8000-000000000007';
    private const TRANSFER_B = '0199f400-0000-7000-8000-000000000008';
    private const ACTOR = '0199f400-0000-7000-8000-000000000009';

    private Connection $db;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->cleanup();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        if ($this->db->isTransactionActive()) {
            $this->db->rollBack();
        }
        $this->cleanup();
        parent::tearDown();
    }

    public function testTransferShipmentSerializesAConcurrentSaleWithoutNegativeStockOrLostUpdate(): void
    {
        $sale = DriverManager::getConnection($this->db->getParams());
        $this->db->beginTransaction();
        $this->tenantContext($this->db);
        $this->db->fetchOne('SELECT id FROM inventory.stock_transfer WHERE id=? FOR UPDATE', [self::TRANSFER_A]);
        $snapshot = $this->db->fetchAssociative('SELECT quantity_on_hand,version FROM inventory.stock WHERE id=? FOR UPDATE', [self::STOCK]);
        self::assertIsArray($snapshot);
        self::assertSame(1, $this->db->executeStatement('UPDATE inventory.stock SET quantity_on_hand=quantity_on_hand-7,version=version+1 WHERE id=? AND version=? AND quantity_on_hand>=7', [self::STOCK, $snapshot['version']]));

        $sale->beginTransaction();
        $this->tenantContext($sale);
        $sale->executeStatement("SET LOCAL lock_timeout='100ms'");
        $blocked = false;
        try {
            $sale->executeStatement('UPDATE inventory.stock SET quantity_on_hand=quantity_on_hand-5,version=version+1 WHERE id=? AND version=? AND quantity_on_hand>=5', [self::STOCK, $snapshot['version']]);
        } catch (Throwable) {
            $blocked = true;
        } finally {
            $sale->rollBack();
        }
        self::assertTrue($blocked, 'A sale must wait while a transfer owns the source stock lock.');
        $this->db->commit();

        $sale->beginTransaction();
        $this->tenantContext($sale);
        self::assertSame(0, $sale->executeStatement('UPDATE inventory.stock SET quantity_on_hand=quantity_on_hand-5,version=version+1 WHERE id=? AND version=? AND quantity_on_hand>=5', [self::STOCK, $snapshot['version']]));
        $sale->commit();
        self::assertSame('3.000000000000', $this->quantity());
        $sale->close();
    }

    public function testTwoIncompatibleTransferShipmentsAreSerializedOnTheirSharedStock(): void
    {
        $transferB = DriverManager::getConnection($this->db->getParams());
        $this->db->beginTransaction();
        $this->tenantContext($this->db);
        $this->db->fetchOne('SELECT id FROM inventory.stock_transfer WHERE id=? FOR UPDATE', [self::TRANSFER_A]);
        $this->db->fetchOne('SELECT id FROM inventory.stock WHERE id=? FOR UPDATE', [self::STOCK]);
        self::assertSame(1, $this->db->executeStatement('UPDATE inventory.stock SET quantity_on_hand=quantity_on_hand-7,version=version+1 WHERE id=? AND quantity_on_hand>=7', [self::STOCK]));

        $transferB->beginTransaction();
        $this->tenantContext($transferB);
        $transferB->fetchOne('SELECT id FROM inventory.stock_transfer WHERE id=? FOR UPDATE', [self::TRANSFER_B]);
        $transferB->executeStatement("SET LOCAL lock_timeout='100ms'");
        $blocked = false;
        try {
            $transferB->fetchOne('SELECT id FROM inventory.stock WHERE id=? FOR UPDATE', [self::STOCK]);
        } catch (Throwable) {
            $blocked = true;
        } finally {
            $transferB->rollBack();
        }
        self::assertTrue($blocked, 'A second transfer must wait for the shared source stock lock.');
        $this->db->commit();

        $transferB->beginTransaction();
        $this->tenantContext($transferB);
        self::assertSame(0, $transferB->executeStatement('UPDATE inventory.stock SET quantity_on_hand=quantity_on_hand-6,version=version+1 WHERE id=? AND quantity_on_hand>=6', [self::STOCK]));
        $transferB->commit();
        self::assertSame('3.000000000000', $this->quantity());
        $transferB->close();
    }

    private function tenantContext(Connection $connection): void
    {
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->executeStatement("SELECT set_config('app.organization_id',?,true)", [self::ORGANIZATION]);
    }

    private function quantity(): string
    {
        return (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE id=?', [self::STOCK]);
    }

    private function fixtures(): void
    {
        $this->db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,'Transfer concurrency','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,'Source','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::SOURCE_STORE, self::ORGANIZATION, 'CONC-SRC', self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,'Destination','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::DESTINATION_STORE, self::ORGANIZATION, 'CONC-DST', self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION]);
        $this->db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,'CONC-SKU','Product','ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [self::PRODUCT, self::ORGANIZATION, self::UNIT, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,10,TRUE,NOW(),?,1)', [self::STOCK, self::ORGANIZATION, self::SOURCE_STORE, self::PRODUCT, self::ACTOR]);
        foreach ([self::TRANSFER_A, self::TRANSFER_B] as $transfer) {
            $this->db->executeStatement("INSERT INTO inventory.stock_transfer (id,organization_id,source_store_id,destination_store_id,status,created_by,created_at,version) VALUES (?,?,?,?,'DRAFT',?,NOW(),1)", [$transfer, self::ORGANIZATION, self::SOURCE_STORE, self::DESTINATION_STORE, self::ACTOR]);
        }
    }

    private function cleanup(): void
    {
        foreach (['inventory.stock_transfer_command', 'inventory.stock_transfer_line', 'inventory.stock_transfer', 'inventory.stock_movement', 'inventory.stock', 'catalog.products', 'catalog.units_of_measure', 'organization.stores'] as $table) {
            $this->db->executeStatement(sprintf('DELETE FROM %s WHERE organization_id=?', $table), [self::ORGANIZATION]);
        }
        $this->db->executeStatement('DELETE FROM organization.organizations WHERE id=?', [self::ORGANIZATION]);
    }
}
