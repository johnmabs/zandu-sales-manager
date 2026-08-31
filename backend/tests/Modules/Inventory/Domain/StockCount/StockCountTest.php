<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Domain\StockCount;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountMode, StockCountScopeType, StockCountStatus};
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, StockCountId, StoreId};

final class StockCountTest extends TestCase
{
    public function testItCreatesABlindDraftWithEmptyProgressByDefault(): void
    {
        $ids = new SymfonyUuidFactory();
        $count = StockCount::create(
            StockCountId::fromString('0199f600-0000-7000-8000-000000000001', $ids),
            OrganizationId::fromString('0199f600-0000-7000-8000-000000000002', $ids),
            StoreId::fromString('0199f600-0000-7000-8000-000000000003', $ids),
            StockCountScopeType::Full,
            ActorId::fromString('0199f600-0000-7000-8000-000000000004', $ids),
            new DateTimeImmutable('2026-08-31T15:00:00+01:00'),
        );

        self::assertSame(StockCountStatus::Draft, $count->status());
        self::assertSame(StockCountMode::Blind, $count->mode());
        self::assertSame(StockCountScopeType::Full, $count->scopeType());
        self::assertSame(0, $count->totalLineCount());
        self::assertSame(0, $count->countedLineCount());
        self::assertSame(0, $count->reconciledLineCount());
        self::assertSame('2026-08-31T14:00:00+00:00', $count->createdAt()->format(DATE_ATOM));
        self::assertSame(1, $count->version());
        self::assertNull($count->startedAt());
        self::assertNull($count->completedAt());
    }

    public function testGuidedPartialModeIsSnapshottedAtCreation(): void
    {
        $ids = new SymfonyUuidFactory();
        $count = StockCount::create(
            StockCountId::fromString('0199f600-0000-7000-8000-000000000011', $ids),
            OrganizationId::fromString('0199f600-0000-7000-8000-000000000012', $ids),
            StoreId::fromString('0199f600-0000-7000-8000-000000000013', $ids),
            StockCountScopeType::Partial,
            ActorId::fromString('0199f600-0000-7000-8000-000000000014', $ids),
            new DateTimeImmutable('2026-08-31T14:00:00Z'),
            StockCountMode::Guided,
            [\Zandu\SharedKernel\Identity\ProductId::fromString('0199f600-0000-7000-8000-000000000015', $ids)],
        );

        self::assertSame(StockCountMode::Guided, $count->mode());
        self::assertSame(StockCountScopeType::Partial, $count->scopeType());
        self::assertCount(1, $count->requestedProductIds());
    }

    public function testPartialScopeMustBeNonEmptyAndUnique(): void
    {
        $ids = new SymfonyUuidFactory();
        $productId = \Zandu\SharedKernel\Identity\ProductId::fromString('0199f600-0000-7000-8000-000000000025', $ids);

        $this->expectException(\Zandu\Modules\Inventory\Domain\InventoryRuleViolation::class);
        StockCount::create(StockCountId::fromString('0199f600-0000-7000-8000-000000000021', $ids), OrganizationId::fromString('0199f600-0000-7000-8000-000000000022', $ids), StoreId::fromString('0199f600-0000-7000-8000-000000000023', $ids), StockCountScopeType::Partial, ActorId::fromString('0199f600-0000-7000-8000-000000000024', $ids), new DateTimeImmutable(), StockCountMode::Blind, [$productId, $productId]);
    }

    public function testDraftCanStartOnlyOnceAndCapturesProgressAudit(): void
    {
        $ids = new SymfonyUuidFactory();
        $actor = ActorId::fromString('0199f600-0000-7000-8000-000000000034', $ids);
        $count = StockCount::create(StockCountId::fromString('0199f600-0000-7000-8000-000000000031', $ids), OrganizationId::fromString('0199f600-0000-7000-8000-000000000032', $ids), StoreId::fromString('0199f600-0000-7000-8000-000000000033', $ids), StockCountScopeType::Full, $actor, new DateTimeImmutable());
        $count->start($actor, new DateTimeImmutable('2026-08-31T20:00:00+01:00'), 2);

        self::assertSame(StockCountStatus::Open, $count->status());
        self::assertSame(2, $count->totalLineCount());
        self::assertSame('2026-08-31T19:00:00+00:00', $count->startedAt()?->format(DATE_ATOM));
        self::assertSame(2, $count->version());

        $this->expectException(\Zandu\Modules\Inventory\Domain\InventoryRuleViolation::class);
        $count->start($actor, new DateTimeImmutable(), 2);
    }

    public function testItCountsOnlyFirstEntriesInProgress(): void
    {
        $ids = new SymfonyUuidFactory();
        $actor = ActorId::fromString('0199f600-0000-7000-8000-000000000044', $ids);
        $count = StockCount::create(StockCountId::fromString('0199f600-0000-7000-8000-000000000041', $ids), OrganizationId::fromString('0199f600-0000-7000-8000-000000000042', $ids), StoreId::fromString('0199f600-0000-7000-8000-000000000043', $ids), StockCountScopeType::Full, $actor, new DateTimeImmutable());
        $count->start($actor, new DateTimeImmutable(), 2);
        $count->registerCountedLines(1);
        $count->registerCountedLines(0);

        self::assertSame(1, $count->countedLineCount());
        self::assertSame(3, $count->version());
    }

    public function testFinalizationRequiresCompleteProgressAndFreezesTheLifecycle(): void
    {
        $ids = new SymfonyUuidFactory();
        $actor = ActorId::fromString('0199f600-0000-7000-8000-000000000054', $ids);
        $count = StockCount::create(StockCountId::fromString('0199f600-0000-7000-8000-000000000051', $ids), OrganizationId::fromString('0199f600-0000-7000-8000-000000000052', $ids), StoreId::fromString('0199f600-0000-7000-8000-000000000053', $ids), StockCountScopeType::Full, $actor, new DateTimeImmutable());
        $count->start($actor, new DateTimeImmutable(), 1);

        try {
            $count->beginFinalization($actor, new DateTimeImmutable());
            self::fail('An incomplete count must not begin finalization.');
        } catch (\Zandu\Modules\Inventory\Domain\InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_HAS_UNCOUNTED_LINES', $exception->errorCode());
        }

        $count->registerCountedLines(1);
        $count->beginFinalization($actor, new DateTimeImmutable('2026-08-31T22:00:00+01:00'));
        self::assertSame(StockCountStatus::Finalizing, $count->status());
        self::assertSame('2026-08-31T21:00:00+00:00', $count->finalizationStartedAt()?->format(DATE_ATOM));
        self::assertSame(4, $count->version());

        $this->expectException(\Zandu\Modules\Inventory\Domain\InventoryRuleViolation::class);
        $count->registerCountedLines(0);
    }

    public function testFinalizingCountTracksReconciliationProgress(): void
    {
        $ids = new SymfonyUuidFactory();
        $actor = ActorId::fromString('0199f600-0000-7000-8000-000000000064', $ids);
        $count = StockCount::create(StockCountId::fromString('0199f600-0000-7000-8000-000000000061', $ids), OrganizationId::fromString('0199f600-0000-7000-8000-000000000062', $ids), StoreId::fromString('0199f600-0000-7000-8000-000000000063', $ids), StockCountScopeType::Full, $actor, new DateTimeImmutable());
        $count->start($actor, new DateTimeImmutable(), 2);
        $count->registerCountedLines(2);
        $count->beginFinalization($actor, new DateTimeImmutable());
        $count->registerReconciledLines(1);

        self::assertSame(1, $count->reconciledLineCount());
        self::assertSame(5, $count->version());

        $this->expectException(\InvalidArgumentException::class);
        $count->registerReconciledLines(2);
    }
}
