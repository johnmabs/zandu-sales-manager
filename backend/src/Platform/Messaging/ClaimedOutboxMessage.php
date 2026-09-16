<?php

declare(strict_types=1);

namespace Zandu\Platform\Messaging;

use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\OutboxMessage;

final readonly class ClaimedOutboxMessage
{
    public function __construct(
        public OutboxMessage $message,
        public Uuid $claimId,
        public int $attempts,
    ) {}
}
