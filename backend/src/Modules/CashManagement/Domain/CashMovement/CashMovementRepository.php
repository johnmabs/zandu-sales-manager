<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashMovement;

use Zandu\SharedKernel\Identity\{CashSessionId, OrganizationId};

interface CashMovementRepository
{
    public function append(CashMovement $movement): void;/** @return list<CashMovement> */
    public function findBySession(OrganizationId $organizationId, CashSessionId $sessionId): array;
}
