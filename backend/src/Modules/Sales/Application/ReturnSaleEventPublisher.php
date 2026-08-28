<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Domain\ReturnSale;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Time\Clock;

final readonly class ReturnSaleEventPublisher
{
    public function __construct(private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock) {}

    /** @param array<string, bool|float|int|string|null> $payload */
    public function publish(string $eventName, ReturnSale $return, ActorContext $actor, array $payload = []): void
    {
        $this->outbox->append(new OutboxMessage(
            OutboxMessageId::generate($this->ids),
            $return->organizationId(),
            'sales.' . $eventName . '.v1',
            [
                'returnSaleId' => $return->id()->toString(),
                'saleId' => $return->saleId()->toString(),
                'storeId' => $return->storeId()->toString(),
                'status' => $return->status()->value,
                ...$payload,
            ],
            $actor->correlationId(),
            $actor->causationId(),
            $this->clock->now(),
        ));
    }
}
