<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Infrastructure\Persistence\Orm;
use LogicException;use Zandu\Modules\CashManagement\Domain\CashMovement\{CashMovement,CashMovementRepository};use Zandu\SharedKernel\Identity\{CashSessionId,OrganizationId};
final class DoctrineCashMovementRepository implements CashMovementRepository
{
 public function append(CashMovement $movement):void{throw new LogicException('Cash movement persistence is implemented in the next increment.');}
 public function findBySession(OrganizationId $organizationId,CashSessionId $sessionId):array{return [];}
}
