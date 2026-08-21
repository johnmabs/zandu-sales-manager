<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final class AllowAllOperationalGuard implements OperationalGuard
{
    public function assertTenant(ActorContext $actorContext, OperationalMode $mode = OperationalMode::Standard): void {}

    public function assertStore(ActorContext $actorContext, StoreId $storeId, OperationalMode $mode = OperationalMode::Standard): void {}
}
