<?php

declare(strict_types=1);

namespace Zandu\Platform\Messaging;

use InvalidArgumentException;
use Throwable;
use Zandu\Platform\Operations\OperationalMetrics;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class OutboxWorker
{
    public function __construct(
        private OutboxQueue $queue,
        private TenantTransaction $transaction,
        private IdGenerator $idGenerator,
        private Clock $clock,
        private OperationalMetrics $metrics,
        private int $claimLeaseSeconds = 30,
        private int $retryDelaySeconds = 30,
        private int $maxAttempts = 5,
    ) {
        if ($claimLeaseSeconds < 1 || $retryDelaySeconds < 1 || $maxAttempts < 1) {
            throw new InvalidArgumentException('Outbox worker durations and maximum attempts must be positive.');
        }
    }

    public function runBatch(
        OrganizationId $organizationId,
        OutboxMessagePublisher $publisher,
        int $limit = 100,
    ): OutboxWorkerResult {
        if ($limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('The outbox batch limit must be between 1 and 1000.');
        }

        $now = $this->clock->now();
        $claimed = $this->transaction->transactional(
            $organizationId,
            fn(): array => $this->queue->claimBatch(
                $organizationId,
                $this->idGenerator->generate(),
                $now,
                $now->modify(sprintf('+%d seconds', $this->claimLeaseSeconds)),
                $limit,
            ),
        );

        $published = 0;
        $scheduledForRetry = 0;
        $deadLettered = 0;

        foreach ($claimed as $message) {
            try {
                $publisher->publish($message->message);
            } catch (Throwable $exception) {
                $deadLetter = $message->attempts + 1 >= $this->maxAttempts;
                $failedAt = $this->clock->now();
                $this->transaction->transactional(
                    $organizationId,
                    fn() => $this->queue->markFailed(
                        $message,
                        $failedAt->modify(sprintf('+%d seconds', $this->retryDelaySeconds)),
                        mb_substr($exception->getMessage(), 0, 1000),
                        $deadLetter,
                    ),
                );
                $this->metrics->increment('outbox_publish_failures');

                if ($deadLetter) {
                    ++$deadLettered;
                    $this->metrics->increment('dead_letter_count');
                } else {
                    ++$scheduledForRetry;
                    $this->metrics->increment('worker_retry_count');
                }

                continue;
            }

            $this->transaction->transactional(
                $organizationId,
                fn() => $this->queue->markPublished($message, $this->clock->now()),
            );
            ++$published;
        }

        return new OutboxWorkerResult(count($claimed), $published, $scheduledForRetry, $deadLettered);
    }
}
