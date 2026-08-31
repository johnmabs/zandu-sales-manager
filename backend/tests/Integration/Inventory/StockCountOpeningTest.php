<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Inventory;

use DateTimeImmutable;
use Doctrine\DBAL\{Connection, DriverManager};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\StartStockCount\{StartStockCount, StartStockCountHandler};
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, StockQuantity};
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountLineRepository, StockCountRepository, StockCountScopeType, StockCountStatus};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementSource, StockMovementType};
use Zandu\Modules\Inventory\Infrastructure\Persistence\{DbalOpenStockCountScopeRepository, DbalStockCountLineRepository, DbalStockCountRepository};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StockId, StockMovementId, StoreId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class StockCountOpeningTest extends KernelTestCase
{
    private const ORGANIZATION = '0199fb00-0000-7000-8000-000000000001';
    private const STORE = '0199fb00-0000-7000-8000-000000000002';
    private const UNIT = '0199fb00-0000-7000-8000-000000000003';
    private const PRODUCT_WITH_STOCK = '0199fb00-0000-7000-8000-000000000004';
    private const PRODUCT_WITHOUT_STOCK = '0199fb00-0000-7000-8000-000000000005';
    private const STOCK = '0199fb00-0000-7000-8000-000000000006';
    private const COUNT_A = '0199fb00-0000-7000-8000-000000000007';
    private const COUNT_B = '0199fb00-0000-7000-8000-000000000008';
    private const ACTOR = '0199fb00-0000-7000-8000-000000000009';
    private const PRODUCT_OUTSIDE_SCOPE = '0199fb00-0000-7000-8000-000000000011';
    private const STOCK_OUTSIDE_SCOPE = '0199fb00-0000-7000-8000-000000000012';

    private Connection $db;
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private DoctrineTenantTransaction $transactions;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(Connection::class);
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->transactions = new DoctrineTenantTransaction($this->db, 'zandu_runtime');
        $this->cleanup();
        $this->fixtures();
    }

    protected function tearDown(): void
    {
        if ($this->db->isTransactionActive()) {
            $this->db->rollBack();
        }
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $this->cleanup();
        parent::tearDown();
    }

    public function testStartCapturesExistingAndMissingStockWithoutCreatingAnEmptyPosition(): void
    {
        $organizationId = $this->organizationId();
        $count = StockCount::create($this->countId(self::COUNT_A), $organizationId, $this->storeId(), StockCountScopeType::Partial, $this->actorId(), new DateTimeImmutable('2026-08-31T19:00:00Z'), requestedProductIds: [$this->productId(self::PRODUCT_WITH_STOCK), $this->productId(self::PRODUCT_WITHOUT_STOCK)]);
        $counts = new DbalStockCountRepository($this->db, $this->ids);
        $this->transactions->transactional($organizationId, fn() => $counts->save($count));
        $lines = new DbalStockCountLineRepository($this->db, $this->ids, $this->decimals);
        $outbox = new OpeningOutbox();
        $handler = new StartStockCountHandler($counts, $lines, new DbalOpenStockCountScopeRepository($this->db), self::getContainer()->get(StockRepository::class), $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), $outbox, new SymfonyUuidV7Generator(), $this->decimals, new FrozenClock(new DateTimeImmutable('2026-08-31T20:00:00Z')));

        $started = $handler(new StartStockCount($count->id(), $this->actor()));

        self::assertSame(StockCountStatus::Open, $started->status());
        self::assertSame(2, $started->totalLineCount());
        $snapshots = $this->transactions->transactional($organizationId, fn(): array => $lines->findByStockCount($organizationId, $count->id()));
        self::assertSame(['10.000000000000', '0.000000000000'], array_map(static fn($line): string => $line->expectedQuantity()->toString(), $snapshots));
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock WHERE organization_id=? AND store_id=? AND product_id=?', [self::ORGANIZATION, self::STORE, self::PRODUCT_WITHOUT_STOCK]));
        self::assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.open_stock_count_scope WHERE organization_id=? AND stock_count_id=?', [self::ORGANIZATION, self::COUNT_A]));
        self::assertSame('inventory.stock_count_started.v1', $outbox->messages[0]->type);
    }

    public function testExclusiveScopeSerializesThenRejectsASecondCountForTheSameProduct(): void
    {
        $this->persistDraft(self::COUNT_A);
        $this->persistDraft(self::COUNT_B);
        $second = DriverManager::getConnection($this->db->getParams());
        $scopeA = new DbalOpenStockCountScopeRepository($this->db);
        $scopeB = new DbalOpenStockCountScopeRepository($second);
        $productIds = [$this->productId(self::PRODUCT_WITH_STOCK)];

        $this->db->beginTransaction();
        $this->tenantContext($this->db);
        $scopeA->acquire($this->organizationId(), $this->storeId(), $this->countId(self::COUNT_A), $productIds);

        $second->beginTransaction();
        $this->tenantContext($second);
        $second->executeStatement("SET LOCAL lock_timeout='100ms'");
        $blocked = false;
        try {
            $scopeB->acquire($this->organizationId(), $this->storeId(), $this->countId(self::COUNT_B), $productIds);
        } catch (Throwable) {
            $blocked = true;
        } finally {
            $second->rollBack();
        }
        self::assertTrue($blocked, 'A concurrent scope acquisition must wait for the same product advisory lock.');
        $this->db->commit();

        $second->beginTransaction();
        $this->tenantContext($second);
        try {
            $scopeB->acquire($this->organizationId(), $this->storeId(), $this->countId(self::COUNT_B), $productIds);
            self::fail('The committed open scope must reject a second stock count.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_ALREADY_OPEN_FOR_PRODUCT', $exception->errorCode());
        } finally {
            $second->rollBack();
            $second->close();
        }
    }

    public function testOpenScopeBlocksItsProductButAllowsAnotherProductMovement(): void
    {
        $this->persistDraft(self::COUNT_A);
        $movements = self::getContainer()->get(StockMovementRepository::class);
        $replayedMovement = $this->idempotentMovement();
        self::assertTrue($this->transactions->transactional($this->organizationId(), fn(): bool => $movements->appendOnce($replayedMovement)));
        $scope = new DbalOpenStockCountScopeRepository($this->db);
        $this->transactions->transactional($this->organizationId(), fn() => $scope->acquire($this->organizationId(), $this->storeId(), $this->countId(self::COUNT_A), [$this->productId(self::PRODUCT_WITH_STOCK)]));
        self::assertFalse($this->transactions->transactional($this->organizationId(), fn(): bool => $movements->appendOnce($replayedMovement)), 'An existing idempotent movement replay remains harmless while the product is scoped.');

        try {
            $this->transactions->transactional($this->organizationId(), fn() => $movements->append($this->movement(self::PRODUCT_WITH_STOCK, self::STOCK, '10', '0199fb00-0000-7000-8000-000000000013')));
            self::fail('A scoped product movement must be rejected.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_PRODUCT_LOCKED', $exception->errorCode());
        }

        $this->transactions->transactional($this->organizationId(), fn() => $movements->append($this->movement(self::PRODUCT_OUTSIDE_SCOPE, self::STOCK_OUTSIDE_SCOPE, '4', '0199fb00-0000-7000-8000-000000000014')));
        self::assertSame(1, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND product_id=?', [self::ORGANIZATION, self::PRODUCT_OUTSIDE_SCOPE]));
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND id=?', [self::ORGANIZATION, '0199fb00-0000-7000-8000-000000000013']));
    }

    private function persistDraft(string $id): void
    {
        $count = StockCount::create($this->countId($id), $this->organizationId(), $this->storeId(), StockCountScopeType::Partial, $this->actorId(), new DateTimeImmutable('2026-08-31T19:00:00Z'), requestedProductIds: [$this->productId(self::PRODUCT_WITH_STOCK)]);
        $this->transactions->transactional($this->organizationId(), fn() => (new DbalStockCountRepository($this->db, $this->ids))->save($count));
    }

    private function tenantContext(Connection $connection): void
    {
        $connection->executeStatement('SET LOCAL ROLE zandu_runtime');
        $connection->executeStatement("SELECT set_config('app.organization_id',?,true)", [self::ORGANIZATION]);
    }

    private function fixtures(): void
    {
        $this->db->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,'Count opening','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'OPEN','Opening store','ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, self::ACTOR, self::ACTOR]);
        $this->db->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [self::UNIT, self::ORGANIZATION]);
        foreach ([[self::PRODUCT_WITH_STOCK, 'OPEN-A'], [self::PRODUCT_WITHOUT_STOCK, 'OPEN-B'], [self::PRODUCT_OUTSIDE_SCOPE, 'OPEN-C']] as [$id, $code]) {
            $this->db->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,?,'Count product','ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [$id, self::ORGANIZATION, $code, self::UNIT, self::ACTOR, self::ACTOR]);
        }
        $this->db->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,10,TRUE,NOW(),?,1)', [self::STOCK, self::ORGANIZATION, self::STORE, self::PRODUCT_WITH_STOCK, self::ACTOR]);
        $this->db->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,4,TRUE,NOW(),?,1)', [self::STOCK_OUTSIDE_SCOPE, self::ORGANIZATION, self::STORE, self::PRODUCT_OUTSIDE_SCOPE, self::ACTOR]);
    }

    private function cleanup(): void
    {
        foreach (['inventory.open_stock_count_scope', 'inventory.stock_count_line', 'inventory.stock_count', 'inventory.stock_movement', 'inventory.stock', 'catalog.products', 'catalog.units_of_measure', 'organization.stores'] as $table) {
            $this->db->executeStatement(sprintf('DELETE FROM %s WHERE organization_id=?', $table), [self::ORGANIZATION]);
        }
        $this->db->executeStatement('DELETE FROM organization.organizations WHERE id=?', [self::ORGANIZATION]);
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION, $this->ids);
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString(self::STORE, $this->ids);
    }
    private function productId(string $id): ProductId
    {
        return ProductId::fromString($id, $this->ids);
    }
    private function countId(string $id): StockCountId
    {
        return StockCountId::fromString($id, $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR, $this->ids);
    }
    private function actor(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0199fb00-0000-7000-8000-000000000010', $this->ids), new DateTimeImmutable('2026-08-31T20:00:00Z'));
    }

    private function movement(string $productId, string $stockId, string $previousQuantity, string $movementId): StockMovement
    {
        return StockMovement::record(StockMovementId::fromString($movementId, $this->ids), $this->organizationId(), $this->storeId(), $this->productId($productId), StockId::fromString($stockId, $this->ids), StockMovementType::AdjustmentIn, new MovementQuantity(Quantity::fromString('1', $this->decimals)), new StockQuantity(Quantity::fromString($previousQuantity, $this->decimals)), StockMovementSource::manualAdjustment(), 'Scope guard test', $this->actorId(), new DateTimeImmutable('2026-08-31T20:05:00Z'));
    }

    private function idempotentMovement(): StockMovement
    {
        return StockMovement::record(StockMovementId::fromString('0199fb00-0000-7000-8000-000000000015', $this->ids), $this->organizationId(), $this->storeId(), $this->productId(self::PRODUCT_WITH_STOCK), StockId::fromString(self::STOCK, $this->ids), StockMovementType::AdjustmentIn, new MovementQuantity(Quantity::fromString('1', $this->decimals)), new StockQuantity(Quantity::fromString('10', $this->decimals)), StockMovementSource::manualAdjustment($this->ids->fromString('0199fb00-0000-7000-8000-000000000016')), 'Idempotent replay test', $this->actorId(), new DateTimeImmutable('2026-08-31T20:04:00Z'));
    }
}

final class OpeningOutbox implements OutboxRepository
{
    /** @var list<OutboxMessage> */
    public array $messages = [];

    public function append(OutboxMessage $message): void
    {
        $this->messages[] = $message;
    }
}
