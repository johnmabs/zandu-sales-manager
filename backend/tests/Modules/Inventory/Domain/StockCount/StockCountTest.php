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
        );

        self::assertSame(StockCountMode::Guided, $count->mode());
        self::assertSame(StockCountScopeType::Partial, $count->scopeType());
    }
}
