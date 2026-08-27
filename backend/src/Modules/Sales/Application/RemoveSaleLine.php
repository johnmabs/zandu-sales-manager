<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{SaleId,SaleLineId};

final readonly class RemoveSaleLine
{
    public function __construct(public SaleId $saleId, public SaleLineId $lineId, public ActorContext $actor) {}
}
