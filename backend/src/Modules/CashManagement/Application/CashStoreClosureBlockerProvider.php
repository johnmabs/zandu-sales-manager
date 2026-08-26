<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application;

use Zandu\Modules\CashManagement\Domain\CashRegister\CashRegisterRepository;
use Zandu\Modules\CashManagement\Domain\CashSession\CashSessionRepository;
use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\SharedKernel\Identity\{OrganizationId,StoreId};

final readonly class CashStoreClosureBlockerProvider implements StoreClosureBlockerProvider
{
    public function __construct(private CashSessionRepository $sessions, private CashRegisterRepository $registers) {}
    public function blockers(OrganizationId $organizationId, StoreId $storeId): array
    {
        foreach ($this->registers->findAll($organizationId, $storeId) as $register) {
            if (null !== $this->sessions->findOpen($organizationId, $register->id())) {
                return ['OPEN_CASH_SESSION'];
            }
        }
        return [];
    }
}
