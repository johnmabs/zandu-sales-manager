<?php

declare(strict_types=1);

namespace Zandu\Platform\Messaging;

final readonly class OutboxWorkerResult
{
    public function __construct(
        public int $claimed,
        public int $published,
        public int $scheduledForRetry,
        public int $deadLettered,
    ) {}
}
