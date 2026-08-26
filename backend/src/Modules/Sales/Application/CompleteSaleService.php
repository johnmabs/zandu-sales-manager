<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Application\Contract\PaymentRecorder;
use Zandu\Modules\Sales\Domain\SaleStatus;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class CompleteSaleService
{
    public function __construct(private TenantTransaction $transaction, private InventoryConsumptionService $inventory, private CashPaymentService $cash, private PaymentRecorder $payments) {}

    public function __invoke(CompleteSale $command): void
    {
        if (SaleStatus::Completed === $command->sale->status()) {
            return;
        }
        $this->transaction->transactional($command->actor->organizationId(), function () use ($command): void {
            $this->inventory->consume($command->sale, $command->products);
            $this->payments->recordCashSale($command->actor->organizationId(), $command->sale->id(), $command->amount, $command->actor->actorId());
            $this->cash->record($command->sale, $command->cashSessionId, $command->amount);
            $command->sale->complete($command->actor, $command->at);
        });
    }
}
