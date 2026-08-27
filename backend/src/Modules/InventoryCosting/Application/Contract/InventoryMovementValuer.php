<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application\Contract;

interface InventoryMovementValuer
{
    /** Must be invoked inside the transaction that persists the physical movement. */
    public function value(ValueInventoryMovement $movement): void;
}
