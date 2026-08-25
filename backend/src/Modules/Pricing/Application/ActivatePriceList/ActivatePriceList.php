<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application\ActivatePriceList;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PriceListId;

final readonly class ActivatePriceList
{
    public function __construct(
        public PriceListId $priceListId,
        public ActorContext $actorContext,
    ) {}
}
