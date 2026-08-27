<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Domain\Sale;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{IdGenerator,OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage,OutboxRepository};
use Zandu\SharedKernel\Time\Clock;

final readonly class SaleEventPublisher
{
    public function __construct(private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock) {}

    /** @param array<string,mixed> $payload */
    public function publish(string $eventName, Sale $sale, ActorContext $actor, array $payload = []): void
    {
        $this->outbox->append(new OutboxMessage(
            OutboxMessageId::generate($this->ids),
            $sale->organizationId(),
            'sales.' . $eventName . '.v1',
            ['saleId' => $sale->id()->toString(), 'storeId' => $sale->storeId()->toString(), 'status' => $sale->status()->value, ...$payload],
            $actor->correlationId(),
            $actor->causationId(),
            $this->clock->now(),
        ));
    }
}
