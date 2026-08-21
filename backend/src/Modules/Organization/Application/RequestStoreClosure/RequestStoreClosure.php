<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\RequestStoreClosure;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class RequestStoreClosure
{
    public function __construct(public StoreId $storeId, public string $reason, public ActorContext $actorContext) {}
}
