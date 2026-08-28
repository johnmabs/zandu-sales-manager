<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ReturnSaleId;

final readonly class CancelReturnSale
{
    public function __construct(public ReturnSaleId $returnSaleId, public ActorContext $actor) {}
}
