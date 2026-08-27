<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashRegister;

use LogicException;
use Zandu\Modules\CashManagement\Domain\CashRegister\{CashRegister,CashRegisterRepository};
use Zandu\Modules\CashManagement\Domain\CashSession\CashSessionRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Identity\{ActorId,CashRegisterId,IdGenerator};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference,SafeAuditMetadata,SecurityAction,SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CashRegisterHandler
{
    public function __construct(private CashRegisterRepository $registers, private CashSessionRepository $sessions, private IdGenerator $ids, private Clock $clock, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $operationalGuard, private SecurityAuditTrail $audit) {}
    public function create(CreateCashRegister $c): CashRegister
    {
        return $this->transaction->transactional($c->actorContext->organizationId(), function () use ($c): CashRegister {
            $this->authorization->authorize($c->actorContext, PermissionCode::CashRegisterCreate, ResourceScope::store($c->actorContext->organizationId(), $c->storeId));
            $this->operationalGuard->assertStore($c->actorContext, $c->storeId);
            $r = CashRegister::create(CashRegisterId::generate($this->ids), $c->actorContext->organizationId(), $c->storeId, $c->code, $c->name, $c->actorContext->actorId(), $this->clock->now());
            $this->registers->save($r);
            return $r;
        });
    }
    public function update(UpdateCashRegister $c): CashRegister
    {
        return $this->transaction->transactional($c->actorContext->organizationId(), function () use ($c): CashRegister {
            $this->authorization->authorize($c->actorContext, PermissionCode::CashRegisterUpdate, ResourceScope::store($c->actorContext->organizationId(), $c->storeId));
            $this->operationalGuard->assertStore($c->actorContext, $c->storeId);
            $r = $this->registers->find($c->actorContext->organizationId(), $c->storeId, $c->id) ?? throw new LogicException('Cash register not found.');
            $r->update($c->code, $c->name, $c->actorContext->actorId(), $this->clock->now());
            $this->registers->save($r);
            return $r;
        });
    }
    public function change(ChangeCashRegisterStatus $c): CashRegister
    {
        return $this->transaction->transactional($c->actorContext->organizationId(), function () use ($c): CashRegister {
            $this->authorization->authorize($c->actorContext, PermissionCode::CashRegisterManage, ResourceScope::store($c->actorContext->organizationId(), $c->storeId));
            $this->operationalGuard->assertStore($c->actorContext, $c->storeId);
            $r = $this->registers->findForUpdate($c->actorContext->organizationId(), $c->storeId, $c->id) ?? throw new LogicException('Cash register not found.');
            if ('archive' === $c->action && null !== $this->sessions->findOpen($c->actorContext->organizationId(), $c->id)) {
                throw new LogicException('Cash register with an open session cannot be archived.');
            }
            match ($c->action) {
                'activate' => $r->activate(), 'deactivate' => $r->deactivate(), 'archive' => $r->archive(), default => throw new LogicException('Unknown cash register action.'),
            };
            $this->registers->save($r);
            if ('archive' === $c->action) {
                $this->audit->recordSuccess($c->actorContext, SecurityAction::CashRegisterArchived, ResourceReference::for('cash_register', $r->id()), SafeAuditMetadata::empty(), $this->clock->now());
            }return $r;
        });
    }
}
