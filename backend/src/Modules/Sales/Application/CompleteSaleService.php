<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use DateTimeZone;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode, StoreBusinessContextProvider};
use Zandu\Modules\Sales\Application\Contract\PaymentRecorder;
use Zandu\Modules\Sales\Application\Contract\SaleCompletionIdempotency;
use Zandu\Modules\Sales\Domain\{SaleRepository,SaleStatus,SalesRuleViolation};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CompleteSaleService
{
    public function __construct(private TenantTransaction $transaction, private SaleRepository $sales, private InventoryConsumptionService $inventory, private SaleLineCostSnapshotService $costSnapshots, private CashPaymentService $cash, private PaymentRecorder $payments, private SaleCompletionIdempotency $idempotency, private AuthorizationService $authorization, private OperationalGuard $operationalGuard, private StoreBusinessContextProvider $stores, private SecurityAuditTrail $audit, private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock, private ?SalePricingService $pricing = null) {}

    public function __invoke(CompleteSale $command): CompleteSaleResult
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): CompleteSaleResult {
            $sale = $this->sales->getForUpdate($command->actor->organizationId(), $command->saleId);
            $this->authorization->authorize($command->actor, PermissionCode::SaleComplete, ResourceScope::store($sale->organizationId(), $sale->storeId()));
            $this->operationalGuard->assertStore($command->actor, $sale->storeId(), OperationalMode::Standard);
            if (!$sale->total()->equals($command->amount)) {
                throw SalesRuleViolation::with('PAYMENT_AMOUNT_MISMATCH', 'Payment amount must equal sale total.');
            }
            $tendered = $command->tenderedAmount ?? $command->amount;
            if (!$tendered->currency()->equals($command->amount->currency())) {
                throw SalesRuleViolation::with('PAYMENT_CURRENCY_MISMATCH', 'Tendered amount currency must match payment currency.');
            }
            if ($tendered->compareTo($command->amount) < 0) {
                throw SalesRuleViolation::with('PAYMENT_AMOUNT_MISMATCH', 'Tendered amount must cover sale total.');
            }
            $change = $tendered->subtract($command->amount);
            $claimed = '' === $command->idempotencyKey || $this->idempotency->claim($sale->id(), $command->idempotencyKey, $this->payloadHash($command));
            if (!$claimed || SaleStatus::Completed === $sale->status()) {
                $paymentId = $this->payments->recordCashSale($command->actor->organizationId(), $sale->id(), $command->amount, $command->actor->actorId());

                return new CompleteSaleResult($sale->id(), $sale->total(), $paymentId, $change);
            }
            $store = $this->stores->provide($sale->organizationId(), $sale->storeId());
            if ($store->currency !== $sale->currency()) {
                throw SalesRuleViolation::with('PAYMENT_CURRENCY_MISMATCH', 'Sale currency must match store currency.');
            }
            $now = $this->clock->now();
            $this->pricing?->assertCurrent($sale, $now);
            $businessDate = $now->setTimezone(new DateTimeZone($store->timeZone))->format('Y-m-d');
            $consumption = $this->inventory->consume($sale, $command->actor);
            $paymentId = $this->payments->recordCashSale($command->actor->organizationId(), $sale->id(), $command->amount, $command->actor->actorId());
            $this->cash->record($sale, $command->cashSessionId, $command->amount, $command->actor->actorId());
            $sale->complete($command->actor, $now, $businessDate);
            $this->sales->save($sale);
            $this->costSnapshots->capture($sale, $consumption);
            $this->audit->recordSuccess($command->actor, SecurityAction::SaleCompleted, ResourceReference::for('sale', $sale->id()), SafeAuditMetadata::fromArray(['businessDate' => $businessDate]), $now);
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $sale->organizationId(),
                'sales.sale_completed.v1',
                ['saleId' => $sale->id()->toString(), 'storeId' => $sale->storeId()->toString(), 'businessDate' => $businessDate, 'total' => $sale->total()->amount()->toString(), 'currency' => $sale->currency()],
                $command->actor->correlationId(),
                $command->actor->causationId(),
                $now,
            ));
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $sale->organizationId(),
                'payments.payment_confirmed.v1',
                ['paymentId' => $paymentId->toString(), 'saleId' => $sale->id()->toString(), 'amount' => $sale->total()->amount()->toString(), 'currency' => $sale->currency()],
                $command->actor->correlationId(),
                $command->actor->causationId(),
                $now,
            ));

            return new CompleteSaleResult($sale->id(), $sale->total(), $paymentId, $change);
        });
    }

    private function payloadHash(CompleteSale $command): string
    {
        return hash('sha256', implode('|', [$command->saleId->toString(), $command->cashSessionId->toString(), $command->amount->amount()->toString(), $command->amount->currency()->code(), $command->tenderedAmount?->amount()->toString() ?? '', $command->tenderedAmount?->currency()->code() ?? '']));
    }
}
