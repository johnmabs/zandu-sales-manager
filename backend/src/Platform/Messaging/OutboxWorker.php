<?php

declare(strict_types=1);

namespace Zandu\Platform\Messaging;

use InvalidArgumentException;
use Throwable;
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
        private int $claimLeaseSeconds = 30,
        private int $initialRetryDelaySeconds = 30,
        private int $maximumRetryDelaySeconds = 3600,
        private int $maxAttempts = 5,
    ) {
        if (
            $claimLeaseSeconds < 1
            || $initialRetryDelaySeconds < 1
            || $maximumRetryDelaySeconds < $initialRetryDelaySeconds
            || $maxAttempts < 1
        ) {
            throw new InvalidArgumentException(
                'Outbox worker durations and maximum attempts must be positive, and the maximum retry delay must not be shorter than the initial delay.',
            );
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
        $failed = 0;

        foreach ($claimed as $message) {
            try {
                $publisher->publish($message->message);
            } catch (Throwable $exception) {
                $permanentlyFailed = $message->attempts + 1 >= $this->maxAttempts;
                $failedAt = $this->clock->now();
                $availableAt = $permanentlyFailed
                    ? $failedAt
                    : $failedAt->modify(sprintf('+%d seconds', $this->retryDelaySeconds($message->attempts)));
                $this->transaction->transactional(
                    $organizationId,
                    fn() => $this->queue->markFailed(
                        $message,
                        $availableAt,
                        mb_substr($exception->getMessage(), 0, 1000),
                        $permanentlyFailed,
                    ),
                );
                if ($permanentlyFailed) {
                    ++$failed;
                } else {
                    ++$scheduledForRetry;
                }

                continue;
            }

            $this->transaction->transactional(
                $organizationId,
                fn() => $this->queue->markPublished($message, $this->clock->now()),
            );
            ++$published;
        }

        return new OutboxWorkerResult(count($claimed), $published, $scheduledForRetry, $failed);
    }

    private function retryDelaySeconds(int $previousAttempts): int
    {
        $delay = $this->initialRetryDelaySeconds;

        for ($attempt = 0; $attempt < $previousAttempts; ++$attempt) {
            if ($delay >= $this->maximumRetryDelaySeconds) {
                return $this->maximumRetryDelaySeconds;
            }

            if ($delay > intdiv($this->maximumRetryDelaySeconds, 2)) {
                return $this->maximumRetryDelaySeconds;
            }

            $delay *= 2;
        }

        return $delay;
    }
}
