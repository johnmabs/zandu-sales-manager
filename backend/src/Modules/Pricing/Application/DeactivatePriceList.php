<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PriceListId;

final readonly class DeactivatePriceList
{
    public function __construct(public PriceListId $priceListId, public ActorContext $actorContext) {}
}
