<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application;

use Zandu\Modules\CashManagement\Domain\CashSession\CashSessionRepository;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\SharedKernel\Access\{PermissionCode,ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{CashSessionId,StoreId};

final readonly class CashSessionQueryService
{
    public function __construct(private CashSessionRepository $sessions, private AuthorizationService $authorization) {}
    /** @return array<string,mixed> */
    public function get(ActorContext $a, StoreId $store, CashSessionId $id): array
    {
        $this->authorization->authorize($a, PermissionCode::CashSessionRead, ResourceScope::store($a->organizationId(), $store));
        $v = $this->sessions->find($a->organizationId(), $store, $id) ?? throw new \InvalidArgumentException('Cash session not found.');
        return ['id' => $v->id()->toString(),'storeId' => $v->storeId()->toString(),'cashRegisterId' => $v->cashRegisterId()->toString(),'status' => $v->status()->value,'openingAmount' => $v->openingBalance()->amount()->toString(),'currency' => $v->openingBalance()->currency()->code(),'countedClosingAmount' => $v->countedClosingBalance()?->amount()->toString(),'expectedClosingAmount' => $v->expectedClosingBalance()?->amount()->toString(),'version' => $v->version()];
    }
}
