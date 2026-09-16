<?php

declare(strict_types=1);

namespace Zandu\Platform\Operations;

use Doctrine\DBAL\Connection;
use RuntimeException;

final readonly class OperationalMetrics
{
    public function __construct(private Connection $connection) {}

    /** @return array<string,int|float> */
    public function snapshot(): array
    {
        $row = $this->connection->fetchAssociative(<<<'SQL'
SELECT COUNT(*) FILTER (WHERE status = 'PENDING') AS outbox_pending_count,
       COALESCE(
           EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(occurred_at) FILTER (WHERE status = 'PENDING'))),
           0
       ) AS outbox_oldest_pending_age,
       COALESCE(SUM(attempts), 0) AS outbox_publish_failures,
       COALESCE(SUM(GREATEST(
           attempts - CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END,
           0
       )), 0) AS worker_retry_count,
       COUNT(*) FILTER (WHERE status = 'FAILED') AS dead_letter_count
FROM messaging.outbox_messages
SQL);

        if (false === $row) {
            throw new RuntimeException('Unable to calculate operational metrics.');
        }

        return [
            'outbox_pending_count' => (int) $row['outbox_pending_count'],
            'outbox_oldest_pending_age' => (float) $row['outbox_oldest_pending_age'],
            'outbox_publish_failures' => (int) $row['outbox_publish_failures'],
            'worker_retry_count' => (int) $row['worker_retry_count'],
            'dead_letter_count' => (int) $row['dead_letter_count'],
        ];
    }
}
