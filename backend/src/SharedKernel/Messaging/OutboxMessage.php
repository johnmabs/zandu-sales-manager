<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Messaging;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OutboxMessageId;

final readonly class OutboxMessage
{
    /** @param array<string, bool|float|int|string|null> $payload */
    public function __construct(
        public OutboxMessageId $id,
        public OrganizationId $organizationId,
        public string $type,
        public array $payload,
        public CorrelationId $correlationId,
        public ?CausationId $causationId,
        public DateTimeImmutable $occurredAt,
    ) {}
}
