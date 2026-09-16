<?php

declare(strict_types=1);

namespace Zandu\Platform\Messaging;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\Uuid;

interface OutboxQueue
{
    /** @return list<ClaimedOutboxMessage> */
    public function claimBatch(
        OrganizationId $organizationId,
        Uuid $claimId,
        DateTimeImmutable $now,
        DateTimeImmutable $claimedUntil,
        int $limit,
    ): array;

    public function markPublished(ClaimedOutboxMessage $claimed, DateTimeImmutable $publishedAt): void;

    public function markFailed(
        ClaimedOutboxMessage $claimed,
        DateTimeImmutable $availableAt,
        string $error,
        bool $deadLetter,
    ): void;
}
