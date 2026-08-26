<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\CashManagement\Application\CashMovement\{RecordCashIn,RecordCashInHandler,RecordCashOut,RecordCashOutHandler,WithdrawCash,WithdrawCashHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{CashSessionId,StoreId,UuidFactory};
use Zandu\SharedKernel\Money\{Currency,Money};

/** @implements ProcessorInterface<mixed,CashMovementResource> */
final readonly class CashMovementProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private DecimalFactory $decimals, private RecordCashInHandler $in, private RecordCashOutHandler $out, private WithdrawCashHandler $withdraw) {}public function process(mixed $d, Operation $o, array $u = [], array $c = []): CashMovementResource
    {
        $a = $this->actors->resolve();
        if (!$d instanceof CashMovementInput) {
            throw new InvalidArgumentException('Input required.');
        }$store = StoreId::fromString((string) ($u['storeId'] ?? throw new InvalidArgumentException('Store identifier is required.')), $this->uuids);
        $session = CashSessionId::fromString((string) ($u['sessionId'] ?? throw new InvalidArgumentException('Session identifier is required.')), $this->uuids);
        $money = Money::fromString($d->amount, Currency::fromCode($d->currency), $this->decimals);
        $m = match ($o->getName()) {
            'cash_in_record' => ($this->in)(new RecordCashIn($store, $session, $money, $d->reason, $a, $d->sourceReference)),'cash_out_record' => ($this->out)(new RecordCashOut($store, $session, $money, $d->reason, $a, $d->sourceReference)),'cash_withdrawal_record' => ($this->withdraw)(new WithdrawCash($store, $session, $money, $d->reason, $a, $d->sourceReference)),default => throw new InvalidArgumentException('Unsupported cash movement operation.'),
        };
        return new CashMovementResource($m->id()->toString(), $m->storeId()->toString(), $m->sessionId()->toString(), $m->type()->value, $m->amount()->amount()->toString(), $m->amount()->currency()->code(), $m->reason(), $m->sourceReference(), $m->occurredAt()->format(DATE_ATOM));
    }
}
