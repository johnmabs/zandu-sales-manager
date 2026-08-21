<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\ReactivateStore;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class ReactivateStore
{
    public function __construct(public StoreId $storeId, public ActorContext $actorContext) {}
}
