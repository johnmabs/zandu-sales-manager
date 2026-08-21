<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\CancelStoreClosure;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class CancelStoreClosure
{
    public function __construct(public StoreId $storeId, public ActorContext $actorContext) {}
}
