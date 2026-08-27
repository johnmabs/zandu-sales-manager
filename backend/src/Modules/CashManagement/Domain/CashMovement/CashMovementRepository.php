<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashMovement;

use Zandu\SharedKernel\Identity\{CashSessionId, OrganizationId, StoreId};

interface CashMovementRepository
{
    public function append(CashMovement $movement): void;
    public function appendOnce(CashMovement $movement): bool;
    /** @return list<CashMovement> */
    public function findBySession(OrganizationId $organizationId, StoreId $storeId, CashSessionId $sessionId): array;
}
