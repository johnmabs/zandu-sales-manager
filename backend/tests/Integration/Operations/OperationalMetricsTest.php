<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Operations;

use Zandu\Platform\Operations\OperationalMetrics;
use Zandu\Tests\Integration\PostgresTestCase;

final class OperationalMetricsTest extends PostgresTestCase
{
    private const ORGANIZATION = '0199b234-3000-7000-8000-000000000001';
    private const ACTOR = '0199b234-3000-7000-8000-000000000002';
    private const PENDING_MESSAGE = '0199b234-3000-7000-8000-000000000011';
    private const FAILED_MESSAGE = '0199b234-3000-7000-8000-000000000012';
    private const CORRELATION = '0199b234-3000-7000-8000-000000000021';

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection->executeStatement(
            "INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?, 'Metrics tenant','ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)",
            [self::ORGANIZATION, self::ACTOR, self::ACTOR],
        );
    }

    protected function tearDown(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM messaging.outbox_messages WHERE organization_id = ?',
            [self::ORGANIZATION],
        );
        $this->connection->executeStatement(
            'DELETE FROM organization.organizations WHERE id = ?',
            [self::ORGANIZATION],
        );

        parent::tearDown();
    }

    public function testMetricsAreCalculatedFromPersistedOutboxState(): void
    {
        $metrics = new OperationalMetrics($this->connection);
        $before = $metrics->snapshot();

        $this->insertMessage(self::PENDING_MESSAGE, 'PENDING', 2);
        $this->insertMessage(self::FAILED_MESSAGE, 'FAILED', 5);

        $after = $metrics->snapshot();

        self::assertSame($before['outbox_pending_count'] + 1, $after['outbox_pending_count']);
        self::assertGreaterThanOrEqual(119, $after['outbox_oldest_pending_age']);
        self::assertSame($before['outbox_publish_failures'] + 7, $after['outbox_publish_failures']);
        self::assertSame($before['worker_retry_count'] + 6, $after['worker_retry_count']);
        self::assertSame($before['dead_letter_count'] + 1, $after['dead_letter_count']);
    }

    private function insertMessage(string $id, string $status, int $attempts): void
    {
        $this->connection->executeStatement(<<<'SQL'
INSERT INTO messaging.outbox_messages (
    id, organization_id, type, payload, correlation_id, occurred_at, available_at, status, attempts
) VALUES (?, ?, 'TestEvent', '{}', ?, NOW() - INTERVAL '2 minutes', NOW(), ?, ?)
SQL, [$id, self::ORGANIZATION, self::CORRELATION, $status, $attempts]);
    }
}
