<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Messaging;

interface OutboxRepository
{
    public function append(OutboxMessage $message): void;
}
