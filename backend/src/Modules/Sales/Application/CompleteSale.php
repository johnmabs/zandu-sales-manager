<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use DateTimeImmutable;
use Zandu\Modules\Sales\Application\Contract\SaleProductDescriptor;
use Zandu\Modules\Sales\Domain\Sale;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\CashSessionId;
use Zandu\SharedKernel\Money\Money;

final readonly class CompleteSale
{
    /** @param list<SaleProductDescriptor> $products */
    public function __construct(public Sale $sale, public Money $amount, public CashSessionId $cashSessionId, public array $products, public ActorContext $actor, public DateTimeImmutable $at) {}
}
