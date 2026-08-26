<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashSession;

use Zandu\SharedKernel\Identity\{CashRegisterId, CashSessionId, OrganizationId, StoreId};

interface CashSessionRepository
{
    public function save(CashSession $session): void;
    public function find(OrganizationId $organizationId, StoreId $storeId, CashSessionId $id): ?CashSession;
    public function findOpen(OrganizationId $organizationId, CashRegisterId $registerId): ?CashSession;
}
