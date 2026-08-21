<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use LogicException;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreStatus;

final readonly class StoreOperationalGuard
{
    public function assertAllows(Store $store, OperationalMode $mode = OperationalMode::Standard): void
    {
        $allowed = match ($store->status()) {
            StoreStatus::Active => true,
            StoreStatus::Suspended => OperationalMode::Standard !== $mode,
            StoreStatus::ClosurePending => OperationalMode::Standard !== $mode,
            StoreStatus::Closed => false,
        };

        if (!$allowed) {
            throw new LogicException(sprintf(
                'Store status "%s" does not allow a %s operation.',
                $store->status()->value,
                strtolower($mode->name),
            ));
        }
    }
}
