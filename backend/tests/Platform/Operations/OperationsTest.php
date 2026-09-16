<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Operations;

use Doctrine\DBAL\Connection;
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
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchAssociative')->willReturn([
            'outbox_pending_count' => '12',
            'outbox_oldest_pending_age' => '45.5',
            'outbox_publish_failures' => '9',
            'worker_retry_count' => '8',
            'dead_letter_count' => '1',
        ]);
        $metrics = new OperationalMetrics($connection);

        self::assertSame([
            'outbox_pending_count' => 12,
            'outbox_oldest_pending_age' => 45.5,
            'outbox_publish_failures' => 9,
            'worker_retry_count' => 8,
            'dead_letter_count' => 1,
        ], $metrics->snapshot());
    }
}
