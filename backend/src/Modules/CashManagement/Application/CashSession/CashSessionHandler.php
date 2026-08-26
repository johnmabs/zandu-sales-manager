<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Application\CashSession;
use LogicException;use Zandu\Modules\CashManagement\Domain\CashRegister\{CashRegisterRepository,CashRegisterStatus};use Zandu\Modules\CashManagement\Domain\CashSession\{CashSession,CashSessionRepository};use Zandu\SharedKernel\Identity\{CashSessionId,IdGenerator};use Zandu\SharedKernel\Tenancy\TenantTransaction;use Zandu\SharedKernel\Time\Clock;
final readonly class CashSessionHandler
{
 public function __construct(private CashSessionRepository $sessions,private CashRegisterRepository $registers,private IdGenerator $ids,private Clock $clock,private TenantTransaction $transaction){}
 public function open(OpenCashSession $c):CashSession{return $this->transaction->transactional($c->actorContext->organizationId(),function()use($c):CashSession{$r=$this->registers->find($c->actorContext->organizationId(),$c->storeId,$c->cashRegisterId)??throw new LogicException('Cash register not found.');if(CashRegisterStatus::Active!==$r->status())throw new LogicException('Cash register is not active.');if(null!==$this->sessions->findOpen($c->actorContext->organizationId(),$c->cashRegisterId))throw new LogicException('Cash register already has an open session.');$s=CashSession::open(CashSessionId::generate($this->ids),$c->actorContext->organizationId(),$c->storeId,$c->cashRegisterId,$c->actorContext->actorId(),$c->openingBalance,$this->clock->now());$this->sessions->save($s);return $s;});}
 public function close(CloseCashSession $c):CashSession{return $this->transaction->transactional($c->actorContext->organizationId(),function()use($c):CashSession{$s=$this->sessions->find($c->actorContext->organizationId(),$c->storeId,$c->sessionId)??throw new LogicException('Cash session not found.');$s->close($c->countedBalance,$c->expectedBalance,$c->actorContext->actorId(),$this->clock->now());$this->sessions->save($s);return $s;});}
}
