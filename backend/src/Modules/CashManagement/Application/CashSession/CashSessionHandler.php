<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashSession;

use LogicException;
use Zandu\Modules\CashManagement\Domain\CashMovement\CashMovementRepository;
use Zandu\Modules\CashManagement\Domain\CashRegister\{CashRegisterRepository,CashRegisterStatus};
use Zandu\Modules\CashManagement\Domain\CashSession\{CashSession,CashSessionRepository};
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard,OperationalMode,StoreBusinessContextProvider};
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{CashSessionId,IdGenerator};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\SecurityAudit\{ResourceReference,SafeAuditMetadata,SecurityAction,SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CashSessionHandler
{
    public function __construct(private CashSessionRepository $sessions, private CashRegisterRepository $registers, private CashMovementRepository $movements, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private DecimalFactory $decimals, private OperationalGuard $operationalGuard, private AuthorizationService $authorization, private SecurityAuditTrail $audit, private StoreBusinessContextProvider $stores) {}
    public function open(OpenCashSession $c): CashSession
    {
        return $this->transaction->transactional($c->actorContext->organizationId(), function () use ($c): CashSession {
            $this->authorization->authorize($c->actorContext, PermissionCode::CashSessionOpen, ResourceScope::store($c->actorContext->organizationId(), $c->storeId));
            $this->operationalGuard->assertStore($c->actorContext, $c->storeId, OperationalMode::Standard);
            $r = $this->registers->findForUpdate($c->actorContext->organizationId(), $c->storeId, $c->cashRegisterId) ?? throw new LogicException('Cash register not found.');
            if (CashRegisterStatus::Active !== $r->status()) {
                throw new LogicException('Cash register is not active.');
            }if (null !== $this->sessions->findOpen($c->actorContext->organizationId(), $c->cashRegisterId)) {
                throw new LogicException('Cash register already has an open session.');
            }
            $store = $this->stores->provide($c->actorContext->organizationId(), $c->storeId);
            if ($store->currency !== $c->openingBalance->currency()->code()) {
                throw new LogicException('Cash session currency must match store currency.');
            }
            $s = CashSession::open(CashSessionId::generate($this->ids), $c->actorContext->organizationId(), $c->storeId, $c->cashRegisterId, $c->actorContext->actorId(), $c->openingBalance, $this->clock->now());
            $this->sessions->save($s);
            $this->audit->recordSuccess($c->actorContext, SecurityAction::CashSessionOpened, ResourceReference::for('cash_session', $s->id()), SafeAuditMetadata::empty(), $s->openedAt());
            return $s;
        });
    }
    public function close(CloseCashSession $c): CashSession
    {
        return $this->transaction->transactional($c->actorContext->organizationId(), function () use ($c): CashSession {
            $this->authorization->authorize($c->actorContext, PermissionCode::CashSessionClose, ResourceScope::store($c->actorContext->organizationId(), $c->storeId));
            $this->operationalGuard->assertStore($c->actorContext, $c->storeId, OperationalMode::Remediation);
            $s = $this->sessions->findForUpdate($c->actorContext->organizationId(), $c->storeId, $c->sessionId) ?? throw new LogicException('Cash session not found.');
            $zero = new Money($this->decimals->fromString('0'), $s->openingBalance()->currency());
            $netMovement = $zero;
            foreach ($this->movements->findBySession($c->actorContext->organizationId(), $c->storeId, $c->sessionId) as $movement) {
                $netMovement = $movement->type()->isIn()
                    ? $netMovement->add($movement->amount())
                    : $netMovement->subtract($movement->amount());
            }
            $s->close($c->countedBalance, $s->calculateExpectedBalance($netMovement), $c->actorContext->actorId(), $this->clock->now());
            $this->sessions->save($s);
            $this->audit->recordSuccess($c->actorContext, SecurityAction::CashSessionClosed, ResourceReference::for('cash_session', $s->id()), SafeAuditMetadata::empty(), $s->closedAt());
            return $s;
        });
    }
}
