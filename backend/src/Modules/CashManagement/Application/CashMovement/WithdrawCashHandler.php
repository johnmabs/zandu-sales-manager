<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashMovement;

use InvalidArgumentException;
use LogicException;
use Zandu\Modules\CashManagement\Domain\CashMovement\{CashMovement,CashMovementRepository,CashMovementType};
use Zandu\Modules\CashManagement\Domain\CashSession\{CashSessionRepository,CashSessionStatus};
use Zandu\SharedKernel\Identity\{CashMovementId,IdGenerator};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference,SafeAuditMetadata,SecurityAction,SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class WithdrawCashHandler
{
    public function __construct(private CashSessionRepository $sessions, private CashMovementRepository $movements, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private SecurityAuditTrail $audit) {}public function __invoke(WithdrawCash $c): CashMovement
    {
        return $this->transaction->transactional($c->actorContext->organizationId(), function () use ($c): CashMovement {
            $s = $this->sessions->find($c->actorContext->organizationId(), $c->storeId, $c->sessionId) ?? throw new LogicException('Cash session not found.');
            if (CashSessionStatus::Open !== $s->status()) {
                throw new LogicException('Cash session is closed.');
            }if ('' === trim($c->reason)) {
                throw new InvalidArgumentException('Cash withdrawal reason is required.');
            }$m = CashMovement::record(CashMovementId::generate($this->ids), $c->actorContext->organizationId(), $c->storeId, $c->sessionId, CashMovementType::CashWithdrawal, $c->amount, $c->sourceReference, $c->reason, $c->actorContext->actorId(), null, $this->clock->now());
            $this->movements->append($m);
            $this->audit->recordSuccess($c->actorContext, SecurityAction::CashWithdrawalRecorded, ResourceReference::for('cash_movement', $m->id()), SafeAuditMetadata::empty(), $m->occurredAt());
            return $m;
        });
    }
}
