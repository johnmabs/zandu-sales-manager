<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashMovement;

use InvalidArgumentException;
use LogicException;
use Zandu\Modules\CashManagement\Domain\CashMovement\{CashMovement,CashMovementRepository,CashMovementType};
use Zandu\Modules\CashManagement\Domain\CashSession\{CashSessionRepository,CashSessionStatus};
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Identity\{CashMovementId,IdGenerator};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference,SafeAuditMetadata,SecurityAction,SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RecordCashOutHandler
{
    public function __construct(private CashSessionRepository $sessions, private CashMovementRepository $movements, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $operationalGuard, private SecurityAuditTrail $audit) {}public function __invoke(RecordCashOut $c): CashMovement
    {
        return $this->transaction->transactional($c->actorContext->organizationId(), function () use ($c): CashMovement {
            $this->authorization->authorize($c->actorContext, PermissionCode::CashOutRecord, ResourceScope::store($c->actorContext->organizationId(), $c->storeId));
            $this->operationalGuard->assertStore($c->actorContext, $c->storeId);
            $s = $this->sessions->findForUpdate($c->actorContext->organizationId(), $c->storeId, $c->sessionId) ?? throw new LogicException('Cash session not found.');
            if (CashSessionStatus::Open !== $s->status()) {
                throw new LogicException('Cash session is closed.');
            }if (!$s->openingBalance()->currency()->equals($c->amount->currency())) {
                throw new LogicException('Cash movement currency must match session currency.');
            }if ('' === trim($c->reason)) {
                throw new InvalidArgumentException('Cash movement reason is required.');
            }$m = CashMovement::record(CashMovementId::generate($this->ids), $c->actorContext->organizationId(), $c->storeId, $c->sessionId, CashMovementType::CashOut, $c->amount, $c->sourceReference, $c->reason, $c->actorContext->actorId(), null, $this->clock->now());
            $this->movements->append($m);
            $this->audit->recordSuccess($c->actorContext, SecurityAction::CashOutRecorded, ResourceReference::for('cash_movement', $m->id()), SafeAuditMetadata::empty(), $m->occurredAt());
            return $m;
        });
    }
}
