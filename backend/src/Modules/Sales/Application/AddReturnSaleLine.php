<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ReturnSaleId, SaleLineId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class AddReturnSaleLine
{
    public function __construct(
        public ReturnSaleId $returnSaleId,
        public SaleLineId $saleLineId,
        public Quantity $quantity,
        public bool $restock,
        public ?string $reason,
        public ActorContext $actor,
    ) {}
}
