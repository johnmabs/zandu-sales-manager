<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SaleId;

final readonly class CancelSale
{
    public function __construct(public SaleId $saleId, public ActorContext $actor) {}
}
