<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Inventory;

use DateTimeImmutable;
use Doctrine\DBAL\{Connection, DriverManager};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\BeginStockCountFinalization\{BeginStockCountFinalization, BeginStockCountFinalizationHandler};
use Zandu\Modules\Inventory\Application\CompleteStockCountFinalization\{CompleteStockCountFinalization, CompleteStockCountFinalizationHandler};
use Zandu\Modules\Inventory\Application\ReconcileStockCount\{ReconcileStockCountBatch, ReconcileStockCountBatchHandler};
use Zandu\Modules\Inventory\Application\RecordStockCount\{RecordStockCount, RecordStockCountBatch, RecordStockCountHandler, StockCountEntry};
use Zandu\Modules\Inventory\Application\StartStockCount\{StartStockCount, StartStockCountHandler};
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, StockQuantity};
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountLineRepository, StockCountReconciliationStatus, StockCountRepository, StockCountScopeType, StockCountStatus};
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

    public function testEntriesSupportExplicitZeroCorrectionBatchAndOptimisticConflict(): void
    {
        $organizationId = $this->organizationId();
        $count = StockCount::create($this->countId(self::COUNT_A), $organizationId, $this->storeId(), StockCountScopeType::Partial, $this->actorId(), new DateTimeImmutable('2026-08-31T19:00:00Z'), requestedProductIds: [$this->productId(self::PRODUCT_WITH_STOCK), $this->productId(self::PRODUCT_WITHOUT_STOCK)]);
        $counts = new DbalStockCountRepository($this->db, $this->ids);
        $lines = new DbalStockCountLineRepository($this->db, $this->ids, $this->decimals);
        $this->transactions->transactional($organizationId, fn() => $counts->save($count));
        $start = new StartStockCountHandler($counts, $lines, new DbalOpenStockCountScopeRepository($this->db), self::getContainer()->get(StockRepository::class), $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), new OpeningOutbox(), new SymfonyUuidV7Generator(), $this->decimals, new FrozenClock(new DateTimeImmutable('2026-08-31T20:00:00Z')));
        $start(new StartStockCount($count->id(), $this->actor()));
        $record = new RecordStockCountHandler($counts, $lines, $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), new FrozenClock(new DateTimeImmutable('2026-08-31T20:10:00Z')));

        $zero = $record(new RecordStockCount($count->id(), $this->productId(self::PRODUCT_WITH_STOCK), Quantity::fromString('0', $this->decimals), 1, $this->actor()));
        self::assertSame('0', $zero->countedQuantity()?->toString());
        self::assertSame(1, $zero->revision());
        self::assertSame(1, $this->transactions->transactional($organizationId, fn(): int => $counts->get($organizationId, $count->id())->countedLineCount()));

        $corrected = $record(new RecordStockCount($count->id(), $this->productId(self::PRODUCT_WITH_STOCK), Quantity::fromString('7', $this->decimals), 2, $this->actor()));
        self::assertSame(2, $corrected->revision());
        self::assertSame(1, $this->transactions->transactional($organizationId, fn(): int => $counts->get($organizationId, $count->id())->countedLineCount()));

        $batch = $record->batch(new RecordStockCountBatch($count->id(), [
            new StockCountEntry($this->productId(self::PRODUCT_WITH_STOCK), Quantity::fromString('8', $this->decimals), 3),
            new StockCountEntry($this->productId(self::PRODUCT_WITHOUT_STOCK), Quantity::fromString('0', $this->decimals), 1),
        ], $this->actor()));
        self::assertCount(2, $batch);
        self::assertSame(2, $this->transactions->transactional($organizationId, fn(): int => $counts->get($organizationId, $count->id())->countedLineCount()));

        try {
            $record->batch(new RecordStockCountBatch($count->id(), [
                new StockCountEntry($this->productId(self::PRODUCT_WITH_STOCK), Quantity::fromString('9', $this->decimals), 4),
                new StockCountEntry($this->productId(self::PRODUCT_WITHOUT_STOCK), Quantity::fromString('1', $this->decimals), 1),
            ], $this->actor()));
            self::fail('A stale line version must roll back the whole batch.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_LINE_VERSION_CONFLICT', $exception->errorCode());
        }
        self::assertSame('8.000000000000', $this->transactions->transactional($organizationId, fn(): ?string => $lines->findByProduct($organizationId, $count->id(), $this->productId(self::PRODUCT_WITH_STOCK))?->countedQuantity()?->toString()));

        $finalizationOutbox = new OpeningOutbox();
        $finalize = new BeginStockCountFinalizationHandler($counts, $lines, $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), $finalizationOutbox, new SymfonyUuidV7Generator(), new FrozenClock(new DateTimeImmutable('2026-08-31T20:20:00Z')));
        $finalizing = $finalize(new BeginStockCountFinalization($count->id(), $this->actor()));
        self::assertSame(StockCountStatus::Finalizing, $finalizing->status());
        self::assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.open_stock_count_scope WHERE organization_id=? AND stock_count_id=?', [self::ORGANIZATION, self::COUNT_A]));
        $finalize(new BeginStockCountFinalization($count->id(), $this->actor()));
        self::assertCount(1, $finalizationOutbox->messages);

        try {
            $record(new RecordStockCount($count->id(), $this->productId(self::PRODUCT_WITH_STOCK), Quantity::fromString('6', $this->decimals), 4, $this->actor()));
            self::fail('Entries must be frozen while finalizing.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_NOT_OPEN', $exception->errorCode());
        }
    }

    public function testReconciliationAppliesInAndOutCorrectionsAndTracksBatchProgress(): void
    {
        [$counts, $lines] = $this->prepareFinalizingCount('7', '2');
        $handler = $this->reconciliationHandler($counts, $lines);

        $result = $handler(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 50, $this->actor()));

        self::assertSame(3, $result->processedCount);
        self::assertSame(0, $result->remainingCount);
        self::assertSame('7.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND store_id=? AND product_id=?', [self::ORGANIZATION, self::STORE, self::PRODUCT_WITH_STOCK]));
        self::assertSame('2.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND store_id=? AND product_id=?', [self::ORGANIZATION, self::STORE, self::PRODUCT_WITHOUT_STOCK]));
        self::assertSame('4.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND store_id=? AND product_id=?', [self::ORGANIZATION, self::STORE, self::PRODUCT_OUTSIDE_SCOPE]));
        self::assertSame(['STOCK_COUNT_CORRECTION_OUT', 'STOCK_COUNT_CORRECTION_IN'], $this->db->fetchFirstColumn('SELECT type FROM inventory.stock_movement WHERE organization_id=? AND source_type=? ORDER BY product_id', [self::ORGANIZATION, 'STOCK_COUNT']));
        self::assertSame(3, $this->transactions->transactional($this->organizationId(), fn(): int => $counts->get($this->organizationId(), $this->countId(self::COUNT_A))->reconciledLineCount()));
        self::assertSame([StockCountReconciliationStatus::Reconciled, StockCountReconciliationStatus::Reconciled, StockCountReconciliationStatus::Reconciled], array_map(static fn($line): StockCountReconciliationStatus => $line->reconciliationStatus(), $this->transactions->transactional($this->organizationId(), fn(): array => $lines->findByStockCount($this->organizationId(), $this->countId(self::COUNT_A)))));

        $replay = $handler(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 50, $this->actor()));
        self::assertSame(0, $replay->processedCount);
        self::assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND source_type=?', [self::ORGANIZATION, 'STOCK_COUNT']));
    }

    public function testSnapshotConflictRollsBackAnEntireReconciliationBatch(): void
    {
        [$counts, $lines] = $this->prepareFinalizingCount('7', '2');
        $this->db->executeStatement('INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,1,TRUE,NOW(),?,1)', ['0199fb00-0000-7000-8000-000000000017', self::ORGANIZATION, self::STORE, self::PRODUCT_WITHOUT_STOCK, self::ACTOR]);

        try {
            ($this->reconciliationHandler($counts, $lines))(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 50, $this->actor()));
            self::fail('A changed physical position must reject the snapshot batch.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_SNAPSHOT_CONFLICT', $exception->errorCode());
        }

        self::assertSame('10.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND product_id=?', [self::ORGANIZATION, self::PRODUCT_WITH_STOCK]));
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND source_type=?', [self::ORGANIZATION, 'STOCK_COUNT']));
        self::assertSame(0, $this->transactions->transactional($this->organizationId(), fn(): int => $counts->get($this->organizationId(), $this->countId(self::COUNT_A))->reconciledLineCount()));
        self::assertSame(3, (int) $this->db->fetchOne("SELECT COUNT(*) FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=? AND reconciliation_status='PENDING'", [self::ORGANIZATION, self::COUNT_A]));
    }

    public function testReconciliationResumesOnlyPendingLinesAfterACommittedBatchAndRestart(): void
    {
        [$counts, $lines] = $this->prepareFinalizingCount('7', '2');
        $firstBatch = ($this->reconciliationHandler($counts, $lines))(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 1, $this->actor()));

        self::assertSame(1, $firstBatch->processedCount);
        self::assertSame(2, $firstBatch->remainingCount);
        self::assertSame('7.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND product_id=?', [self::ORGANIZATION, self::PRODUCT_WITH_STOCK]));
        $committedMovementId = (string) $this->db->fetchOne('SELECT id FROM inventory.stock_movement WHERE organization_id=? AND product_id=? AND source_type=?', [self::ORGANIZATION, self::PRODUCT_WITH_STOCK, 'STOCK_COUNT']);

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $restartedCounts = new DbalStockCountRepository($this->db, $this->ids);
        $restartedLines = new DbalStockCountLineRepository($this->db, $this->ids, $this->decimals);
        $restartedHandler = $this->reconciliationHandler($restartedCounts, $restartedLines);

        $secondBatch = $restartedHandler(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 1, $this->actor()));
        self::assertSame(1, $secondBatch->processedCount);
        self::assertSame(1, $secondBatch->remainingCount);
        self::assertSame('7.000000000000', (string) $this->db->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND product_id=?', [self::ORGANIZATION, self::PRODUCT_WITH_STOCK]));
        self::assertSame($committedMovementId, (string) $this->db->fetchOne('SELECT id FROM inventory.stock_movement WHERE organization_id=? AND product_id=? AND source_type=?', [self::ORGANIZATION, self::PRODUCT_WITH_STOCK, 'STOCK_COUNT']));
        self::assertSame('RECONCILED', (string) $this->db->fetchOne('SELECT reconciliation_status FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=? AND product_id=?', [self::ORGANIZATION, self::COUNT_A, self::PRODUCT_WITH_STOCK]));
        self::assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND source_type=?', [self::ORGANIZATION, 'STOCK_COUNT']));

        $thirdBatch = $restartedHandler(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 1, $this->actor()));
        self::assertSame(1, $thirdBatch->processedCount);
        self::assertSame(0, $thirdBatch->remainingCount);
        self::assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND source_type=?', [self::ORGANIZATION, 'STOCK_COUNT']), 'The zero-variance resumed line must not create a movement.');
        self::assertSame(3, $this->transactions->transactional($this->organizationId(), fn(): int => $restartedCounts->get($this->organizationId(), $this->countId(self::COUNT_A))->reconciledLineCount()));
        self::assertSame(0, (int) $this->db->fetchOne("SELECT COUNT(*) FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=? AND reconciliation_status='PENDING'", [self::ORGANIZATION, self::COUNT_A]));

        $finishedReplay = $restartedHandler(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 1, $this->actor()));
        self::assertSame(0, $finishedReplay->processedCount);
        self::assertSame(0, $finishedReplay->remainingCount);
        self::assertSame(2, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND source_type=?', [self::ORGANIZATION, 'STOCK_COUNT']));
    }

    public function testCompletionReleasesScopesPublishesOnceAndPreservesLines(): void
    {
        [$counts, $lines] = $this->prepareFinalizingCount('7', '2');
        ($this->reconciliationHandler($counts, $lines))(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 50, $this->actor()));
        $outbox = new OpeningOutbox();
        $handler = $this->completionHandler($counts, $lines, $outbox);

        $completed = $handler(new CompleteStockCountFinalization($this->countId(self::COUNT_A), $this->actor()));

        self::assertSame(StockCountStatus::Completed, $completed->status());
        self::assertSame(self::ACTOR, $completed->completedBy()?->toString());
        self::assertSame('2026-08-31T20:40:00+00:00', $completed->completedAt()?->format(DATE_ATOM));
        self::assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.open_stock_count_scope WHERE organization_id=? AND stock_count_id=?', [self::ORGANIZATION, self::COUNT_A]));
        self::assertSame(3, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=?', [self::ORGANIZATION, self::COUNT_A]));
        self::assertCount(1, $outbox->messages);
        self::assertSame('inventory.stock_count_completed.v1', $outbox->messages[0]->type);
        self::assertSame(self::COUNT_A, $outbox->messages[0]->payload['stockCountId']);

        self::assertSame(StockCountStatus::Completed, $handler(new CompleteStockCountFinalization($this->countId(self::COUNT_A), $this->actor()))->status());
        self::assertCount(1, $outbox->messages);
    }

    public function testCompletionRefusesPendingLinesAndKeepsScopes(): void
    {
        [$counts, $lines] = $this->prepareFinalizingCount('7', '2');
        ($this->reconciliationHandler($counts, $lines))(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 1, $this->actor()));
        $outbox = new OpeningOutbox();

        try {
            ($this->completionHandler($counts, $lines, $outbox))(new CompleteStockCountFinalization($this->countId(self::COUNT_A), $this->actor()));
            self::fail('A stock count with pending lines must remain finalizing.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_HAS_PENDING_LINES', $exception->errorCode());
        }

        self::assertSame(StockCountStatus::Finalizing, $this->transactions->transactional($this->organizationId(), fn(): StockCountStatus => $counts->get($this->organizationId(), $this->countId(self::COUNT_A))->status()));
        self::assertSame(3, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.open_stock_count_scope WHERE organization_id=? AND stock_count_id=?', [self::ORGANIZATION, self::COUNT_A]));
        self::assertCount(0, $outbox->messages);
    }

    public function testCompletionRollsBackStatusAndScopeReleaseWhenOutboxFails(): void
    {
        [$counts, $lines] = $this->prepareFinalizingCount('7', '2');
        ($this->reconciliationHandler($counts, $lines))(new ReconcileStockCountBatch($this->countId(self::COUNT_A), 50, $this->actor()));

        try {
            ($this->completionHandler($counts, $lines, new FailingOpeningOutbox()))(new CompleteStockCountFinalization($this->countId(self::COUNT_A), $this->actor()));
            self::fail('An outbox failure must abort stock count completion.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Simulated stock count outbox failure.', $exception->getMessage());
        }

        $persisted = $this->transactions->transactional($this->organizationId(), fn(): StockCount => $counts->get($this->organizationId(), $this->countId(self::COUNT_A)));
        self::assertSame(StockCountStatus::Finalizing, $persisted->status());
        self::assertNull($persisted->completedBy());
        self::assertNull($persisted->completedAt());
        self::assertSame(3, (int) $this->db->fetchOne('SELECT COUNT(*) FROM inventory.open_stock_count_scope WHERE organization_id=? AND stock_count_id=?', [self::ORGANIZATION, self::COUNT_A]));
    }

    /** @return array{DbalStockCountRepository, DbalStockCountLineRepository} */
    private function prepareFinalizingCount(string $existingCount, string $missingCount): array
    {
        $organizationId = $this->organizationId();
        $count = StockCount::create($this->countId(self::COUNT_A), $organizationId, $this->storeId(), StockCountScopeType::Partial, $this->actorId(), new DateTimeImmutable('2026-08-31T19:00:00Z'), requestedProductIds: [$this->productId(self::PRODUCT_WITH_STOCK), $this->productId(self::PRODUCT_WITHOUT_STOCK), $this->productId(self::PRODUCT_OUTSIDE_SCOPE)]);
        $counts = new DbalStockCountRepository($this->db, $this->ids);
        $lines = new DbalStockCountLineRepository($this->db, $this->ids, $this->decimals);
        $this->transactions->transactional($organizationId, fn() => $counts->save($count));
        (new StartStockCountHandler($counts, $lines, new DbalOpenStockCountScopeRepository($this->db), self::getContainer()->get(StockRepository::class), $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), new OpeningOutbox(), new SymfonyUuidV7Generator(), $this->decimals, new FrozenClock(new DateTimeImmutable('2026-08-31T20:00:00Z'))))(new StartStockCount($count->id(), $this->actor()));
        $record = new RecordStockCountHandler($counts, $lines, $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), new FrozenClock(new DateTimeImmutable('2026-08-31T20:10:00Z')));
        $record->batch(new RecordStockCountBatch($count->id(), [
            new StockCountEntry($this->productId(self::PRODUCT_WITH_STOCK), Quantity::fromString($existingCount, $this->decimals), 1),
            new StockCountEntry($this->productId(self::PRODUCT_WITHOUT_STOCK), Quantity::fromString($missingCount, $this->decimals), 1),
            new StockCountEntry($this->productId(self::PRODUCT_OUTSIDE_SCOPE), Quantity::fromString('4', $this->decimals), 1),
        ], $this->actor()));
        (new BeginStockCountFinalizationHandler($counts, $lines, $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), new OpeningOutbox(), new SymfonyUuidV7Generator(), new FrozenClock(new DateTimeImmutable('2026-08-31T20:20:00Z'))))(new BeginStockCountFinalization($count->id(), $this->actor()));

        return [$counts, $lines];
    }

    private function reconciliationHandler(DbalStockCountRepository $counts, DbalStockCountLineRepository $lines): ReconcileStockCountBatchHandler
    {
        return new ReconcileStockCountBatchHandler($counts, $lines, self::getContainer()->get(StockRepository::class), self::getContainer()->get(StockMovementRepository::class), $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), new SymfonyUuidV7Generator(), $this->decimals, new FrozenClock(new DateTimeImmutable('2026-08-31T20:30:00Z')));
    }

    private function completionHandler(DbalStockCountRepository $counts, DbalStockCountLineRepository $lines, OutboxRepository $outbox): CompleteStockCountFinalizationHandler
    {
        return new CompleteStockCountFinalizationHandler($counts, $lines, new DbalOpenStockCountScopeRepository($this->db), $this->transactions, $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), $outbox, new SymfonyUuidV7Generator(), new FrozenClock(new DateTimeImmutable('2026-08-31T20:40:00Z')));
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

final class FailingOpeningOutbox implements OutboxRepository
{
    public function append(OutboxMessage $message): void
    {
        throw new \RuntimeException('Simulated stock count outbox failure.');
    }
}
