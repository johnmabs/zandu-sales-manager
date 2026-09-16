<?php

declare(strict_types=1);

namespace Zandu\Platform\Messaging;

use Zandu\SharedKernel\Messaging\OutboxMessage;

interface OutboxMessagePublisher
{
    public function publish(OutboxMessage $message): void;
}
