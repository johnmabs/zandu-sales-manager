<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{CashSessionId, SaleId};
use Zandu\SharedKernel\Money\Money;

final readonly class CompleteSale
{
    public function __construct(public SaleId $saleId, public Money $amount, public CashSessionId $cashSessionId, public ActorContext $actor, public string $idempotencyKey = '', public ?Money $tenderedAmount = null) {}
}
