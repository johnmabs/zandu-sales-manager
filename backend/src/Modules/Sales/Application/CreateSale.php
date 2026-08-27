<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class CreateSale
{
    public function __construct(public StoreId $storeId, public ActorContext $actor) {}
}
