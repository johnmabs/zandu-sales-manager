<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId,ProductPackagingId,SaleId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class AddSaleLine
{
    public function __construct(public SaleId $saleId, public ProductId $productId, public ProductPackagingId $productPackagingId, public Quantity $quantity, public ActorContext $actor) {}
}
