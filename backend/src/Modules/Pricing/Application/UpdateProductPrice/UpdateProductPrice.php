<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application\UpdateProductPrice;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Money\Money;

final readonly class UpdateProductPrice
{
    public function __construct(
        public ProductPriceId $productPriceId,
        public Money $amount,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validTo,
        public ActorContext $actorContext,
    ) {}
}
