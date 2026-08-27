<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{SaleId,SaleLineId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class UpdateSaleLine
{
    public function __construct(public SaleId $saleId, public SaleLineId $lineId, public Quantity $quantity, public ActorContext $actor) {}
}
