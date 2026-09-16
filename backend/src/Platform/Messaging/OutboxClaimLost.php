<?php

declare(strict_types=1);

namespace Zandu\Platform\Messaging;

use RuntimeException;

final class OutboxClaimLost extends RuntimeException
{
    public function __construct(ClaimedOutboxMessage $claimed)
    {
        parent::__construct(sprintf(
            'The claim for outbox message "%s" is no longer current.',
            $claimed->message->id->toString(),
        ));
    }
}
