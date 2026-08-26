<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashRegister;

use Zandu\SharedKernel\Identity\{CashRegisterId, OrganizationId, StoreId};

interface CashRegisterRepository
{
    public function save(CashRegister $register): void;
    public function find(OrganizationId $organizationId, StoreId $storeId, CashRegisterId $id): ?CashRegister;
    /** @return list<CashRegister> */ public function findAll(OrganizationId $organizationId, StoreId $storeId): array;
}
