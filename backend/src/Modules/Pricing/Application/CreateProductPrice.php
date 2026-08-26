<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{PriceListId,ProductId,ProductPackagingId};
use Zandu\SharedKernel\Money\Money;

final readonly class CreateProductPrice
{
    public function __construct(public PriceListId $priceListId, public ProductId $productId, public ProductPackagingId $packagingId, public Money $amount, public ?DateTimeImmutable $validFrom, public ?DateTimeImmutable $validTo, public ActorContext $actorContext) {}
}
