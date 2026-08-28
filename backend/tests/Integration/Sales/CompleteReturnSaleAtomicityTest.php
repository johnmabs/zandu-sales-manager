<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Sales;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockRestocker, RestockSaleReturn};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, StoreBusinessContextProvider};
use Zandu\Modules\Sales\Application\{CompleteReturnSale, CompleteReturnSaleService, ReturnAmountCalculator, ReturnSaleEventPublisher};
use Zandu\Modules\Sales\Domain\{ReturnSaleRepository, SaleRepository};
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ReturnSaleId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CompleteReturnSaleAtomicityTest extends KernelTestCase
{
    private const ORGANIZATION = '019a3900-0000-7000-8000-000000000001';
    private const ACTOR = '019a3900-0000-7000-8000-000000000002';
    private const STORE = '019a3900-0000-7000-8000-000000000003';
    private const UNIT = '019a3900-0000-7000-8000-000000000004';
    private const PRODUCT = '019a3900-0000-7000-8000-000000000005';
    private const STOCK = '019a3900-0000-7000-8000-000000000006';
    private const SALE = '019a3900-0000-7000-8000-000000000007';
    private const SALE_LINE = '019a3900-0000-7000-8000-000000000008';
    private const SALE_MOVEMENT = '019a3900-0000-7000-8000-000000000009';
    private const VALUATION = '019a3900-0000-7000-8000-000000000010';
    private const RETURN = '019a3900-0000-7000-8000-000000000011';
    private const RETURN_LINE = '019a3900-0000-7000-8000-000000000012';

    private Connection $connection;
    private SymfonyUuidFactory $uuids;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);
        $this->uuids = new SymfonyUuidFactory();
        $this->cleanup();
        $this->fixture();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    #[DataProvider('failurePhases')]
    public function testFailureRollsBackReturnInventoryCostingAuditAndOutbox(string $phase): void
    {
        try {
            ($this->service($phase))(new CompleteReturnSale($this->returnId(), $this->actor()));
            self::fail('The injected failure should abort return completion.');
        } catch (RuntimeException $exception) {
            self::assertSame('Injected ' . $phase . ' failure.', $exception->getMessage());
        }

        $this->assertInitialState();
    }

    public function testSuccessfulCompletionCommitsEveryReturnEffectTogether(): void
    {
        $completed = ($this->service(null))(new CompleteReturnSale($this->returnId(), $this->actor()));

        self::assertSame('COMPLETED', $completed->status()->value);
        self::assertSame('14.000000000000', $this->value('SELECT quantity_on_hand FROM inventory.stock WHERE id = ?', self::STOCK));
        self::assertSame('14.000000000000', $this->value('SELECT quantity_on_hand FROM inventory_costing.stock_valuation WHERE id = ?', self::VALUATION));
        self::assertSame('5600.000000', $this->value('SELECT total_value FROM inventory_costing.stock_valuation WHERE id = ?', self::VALUATION));
        self::assertSame(1, $this->typedCount('inventory.stock_movement', 'SALE_RETURN'));
        self::assertSame(1, $this->typedCount('inventory_costing.stock_valuation_movement', 'SALE_RETURN'));
        self::assertSame(1, $this->rowCount('sales.return_sale_line_amount'));
        self::assertSame(1, $this->rowCount('security.security_audit_entries'));
        self::assertSame(2, $this->rowCount('messaging.outbox_messages'));
    }

    /** @return iterable<string, array{string}> */
    public static function failurePhases(): iterable
    {
        yield 'inventory' => ['inventory'];
        yield 'audit' => ['audit'];
        yield 'outbox' => ['outbox'];
        yield 'before_commit' => ['before_commit'];
    }

    private function service(?string $phase): CompleteReturnSaleService
    {
        $container = self::getContainer();
        $transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $clock = new FrozenClock(new DateTimeImmutable('2026-08-28T14:00:00Z'));
        $inventory = $container->get(InventoryStockRestocker::class);

        return new CompleteReturnSaleService(
            'before_commit' === $phase ? new FailingReturnBeforeCommitTransaction($transaction) : $transaction,
            $container->get(SaleRepository::class),
            $container->get(ReturnSaleRepository::class),
            new ReturnAmountCalculator(),
            'inventory' === $phase ? new FailingAfterReturnRestock($inventory) : $inventory,
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
            'audit' === $phase ? new FailingReturnSecurityAuditTrail() : $container->get(SecurityAuditTrail::class),
            new ReturnSaleEventPublisher(
                'outbox' === $phase ? new FailingReturnOutboxRepository() : $container->get(OutboxRepository::class),
                new SymfonyUuidV7Generator(),
                $clock,
            ),
            $container->get(StoreBusinessContextProvider::class),
            $clock,
        );
    }

    private function assertInitialState(): void
    {
        self::assertSame('DRAFT', $this->value('SELECT status FROM sales.return_sale WHERE id = ?', self::RETURN));
        self::assertSame('8.000000000000', $this->value('SELECT quantity_on_hand FROM inventory.stock WHERE id = ?', self::STOCK));
        self::assertSame('8.000000000000', $this->value('SELECT quantity_on_hand FROM inventory_costing.stock_valuation WHERE id = ?', self::VALUATION));
        self::assertSame('3200.000000', $this->value('SELECT total_value FROM inventory_costing.stock_valuation WHERE id = ?', self::VALUATION));
        self::assertSame(0, $this->typedCount('inventory.stock_movement', 'SALE_RETURN'));
        self::assertSame(0, $this->typedCount('inventory_costing.stock_valuation_movement', 'SALE_RETURN'));
        self::assertSame(0, $this->rowCount('sales.return_sale_line_amount'));
        self::assertSame(0, $this->rowCount('security.security_audit_entries'));
        self::assertSame(0, $this->rowCount('messaging.outbox_messages'));
    }

    private function fixture(): void
    {
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, 'Return atomicity', self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,?,'ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, 'ATM', 'Atomic Return Store', self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,?,?,'COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION, 'EA-ATM', 'Atomic unit']);
        $this->connection->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,?,?,'ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [self::PRODUCT, self::ORGANIZATION, 'SKU-ATM', 'Atomic product', self::UNIT, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,8,TRUE,NOW(),?,2)', [self::STOCK, self::ORGANIZATION, self::STORE, self::PRODUCT, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale (id,organization_id,store_id,status,currency,subtotal,discount_total,tax_total,total,created_by,created_at,completed_by,completed_at,business_date,version) VALUES (?,?,?,'COMPLETED','XAF',18000,0,0,18000,?,NOW(),?,NOW(),CURRENT_DATE,2)", [self::SALE, self::ORGANIZATION, self::STORE, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale_line (id,organization_id,sale_id,line_number,product_id,product_packaging_id,product_code_snapshot,product_name_snapshot,packaging_code_snapshot,packaging_name_snapshot,unit_id_snapshot,entered_quantity,conversion_factor_snapshot,base_quantity,unit_price,discount_amount,taxable_amount,tax_amount,subtotal,total,source_versions) VALUES (?,?,?,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CAST(? AS JSONB))", [self::SALE_LINE, self::ORGANIZATION, self::SALE, self::PRODUCT, self::UNIT, 'SKU-ATM', 'Atomic product', 'PACK-6', 'Pack of six', self::UNIT, '2', '6', '12', '9000', '0', '18000', '0', '18000', '18000', '{}']);
        $this->connection->executeStatement("INSERT INTO inventory.stock_movement (id,organization_id,store_id,product_id,stock_id,type,quantity,previous_quantity,resulting_quantity,source_type,source_reference_id,performed_by,occurred_at) VALUES (?,?,?,?,?,'SALE',2,10,8,'SALE',?,?,NOW())", [self::SALE_MOVEMENT, self::ORGANIZATION, self::STORE, self::PRODUCT, self::STOCK, self::SALE, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale_line_cost_snapshot (organization_id,sale_line_id,stock_id,stock_movement_id,quantity,unit_cost,total_cost,currency,valuation_version,occurred_at) VALUES (?,?,?,?,12,400,4800,'XAF',2,NOW())", [self::ORGANIZATION, self::SALE_LINE, self::STOCK, self::SALE_MOVEMENT]);
        $this->connection->executeStatement("INSERT INTO inventory_costing.stock_valuation (id,organization_id,store_id,product_id,stock_id,quantity_on_hand,total_value,currency,version) VALUES (?,?,?,?,?,8,3200,'XAF',2)", [self::VALUATION, self::ORGANIZATION, self::STORE, self::PRODUCT, self::STOCK]);
        $this->connection->executeStatement("INSERT INTO sales.return_sale (id,organization_id,store_id,sale_id,status,reason,created_by,created_at,version) VALUES (?,?,?,?,'DRAFT','Customer return',?,NOW(),2)", [self::RETURN, self::ORGANIZATION, self::STORE, self::SALE, self::ACTOR]);
        $this->connection->executeStatement('INSERT INTO sales.return_sale_line (id,organization_id,return_sale_id,sale_id,line_number,sale_line_id,product_id,returned_quantity,base_returned_quantity,restock,reason) VALUES (?,?,?,?,1,?,?,1,6,TRUE,?)', [self::RETURN_LINE, self::ORGANIZATION, self::RETURN, self::SALE, self::SALE_LINE, self::PRODUCT, 'Sealed item']);
    }

    private function cleanup(): void
    {
        foreach (['messaging.outbox_messages', 'security.security_audit_entries', 'inventory_costing.stock_valuation_movement', 'sales.return_sale_line_amount', 'sales.return_sale_line', 'sales.return_sale', 'sales.sale_line_cost_snapshot', 'inventory_costing.stock_valuation', 'inventory.stock_movement', 'inventory.stock', 'sales.sale_line', 'sales.sale', 'catalog.products', 'catalog.units_of_measure', 'organization.stores'] as $table) {
            $this->connection->executeStatement('DELETE FROM ' . $table . ' WHERE organization_id = ?', [self::ORGANIZATION]);
        }
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }

    private function rowCount(string $table): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ' . $table . ' WHERE organization_id = ?', [self::ORGANIZATION]);
    }

    private function typedCount(string $table, string $type): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ' . $table . ' WHERE organization_id = ? AND type = ?', [self::ORGANIZATION, $type]);
    }

    private function value(string $sql, string $id): string
    {
        return (string) $this->connection->fetchOne($sql, [$id]);
    }

    private function returnId(): ReturnSaleId
    {
        return ReturnSaleId::fromString(self::RETURN, $this->uuids);
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString(self::ACTOR, $this->uuids),
            OrganizationId::fromString(self::ORGANIZATION, $this->uuids),
            ActorType::User,
            CorrelationId::fromString('019a3900-0000-7000-8000-000000000013', $this->uuids),
            new DateTimeImmutable('2026-08-28T13:00:00Z'),
        );
    }
}

final readonly class FailingAfterReturnRestock implements InventoryStockRestocker
{
    public function __construct(private InventoryStockRestocker $inventory) {}

    public function restockSaleReturn(RestockSaleReturn $request): never
    {
        $this->inventory->restockSaleReturn($request);

        throw new RuntimeException('Injected inventory failure.');
    }
}

final readonly class FailingReturnBeforeCommitTransaction implements TenantTransaction
{
    public function __construct(private TenantTransaction $transaction) {}

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $this->transaction->transactional($organizationId, function () use ($operation): never {
            $operation();

            throw new RuntimeException('Injected before_commit failure.');
        });
    }
}

final class FailingReturnSecurityAuditTrail implements SecurityAuditTrail
{
    public function recordSuccess(ActorContext $actor, SecurityAction $action, ResourceReference $resource, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt, ?OrganizationId $organizationId = null): never
    {
        throw new RuntimeException('Injected audit failure.');
    }

    public function recordDenied(ActorContext $actor, ResourceReference $resource, string $reason, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt): never
    {
        throw new RuntimeException('Injected audit failure.');
    }
}

final class FailingReturnOutboxRepository implements OutboxRepository
{
    public function append(OutboxMessage $message): never
    {
        throw new RuntimeException('Injected outbox failure.');
    }
}
