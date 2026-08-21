<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Operations;

use PHPUnit\Framework\TestCase;
use Zandu\Platform\Operations\GracefulShutdown;
use Zandu\Platform\Operations\OperationalMetrics;

final class OperationsTest extends TestCase
{
    public function testGracefulShutdownCanBeRequestedCooperatively(): void
    {
        $shutdown = new GracefulShutdown();
        self::assertFalse($shutdown->isRequested());

        $shutdown->request();

        self::assertTrue($shutdown->isRequested());
    }

    public function testRequiredWorkerAndOutboxMetricsAreAvailable(): void
    {
        $metrics = new OperationalMetrics();
        $metrics->setGauge('outbox_pending_count', 12);
        $metrics->setGauge('outbox_oldest_pending_age', 45.5);
        $metrics->increment('outbox_publish_failures');
        $metrics->increment('worker_retry_count');
        $metrics->increment('dead_letter_count');

        self::assertSame([
            'outbox_pending_count' => 12,
            'outbox_oldest_pending_age' => 45.5,
            'outbox_publish_failures' => 1,
            'worker_retry_count' => 1,
            'dead_letter_count' => 1,
        ], $metrics->snapshot());
    }
}
