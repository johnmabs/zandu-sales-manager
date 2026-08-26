<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application;

use Zandu\Modules\CashManagement\Domain\CashMovement\CashMovementRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{CashSessionId,StoreId};

final readonly class CashMovementQueryService
{
    public function __construct(private CashMovementRepository $movements, private AuthorizationService $authorization) {}
    /** @return list<array<string,mixed>> */
    public function list(ActorContext $a, StoreId $store, CashSessionId $session): array
    {
        $this->authorization->authorize($a, PermissionCode::CashMovementRead, ResourceScope::store($a->organizationId(), $store));
        return array_map($this->view(...), $this->movements->findBySession($a->organizationId(), $session));
    }
    /** @return array<string,mixed> */
    private function view(\Zandu\Modules\CashManagement\Domain\CashMovement\CashMovement $m): array
    {
        return ['id' => $m->id()->toString(),'storeId' => $m->storeId()->toString(),'sessionId' => $m->sessionId()->toString(),'type' => $m->type()->value,'amount' => $m->amount()->amount()->toString(),'currency' => $m->amount()->currency()->code(),'reason' => $m->reason(),'sourceReference' => $m->sourceReference(),'occurredAt' => $m->occurredAt()->format(DATE_ATOM)];
    }
}
