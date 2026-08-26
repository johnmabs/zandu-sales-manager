<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application;

final class ProductPriceStatusCommands {}

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductPriceId;

final readonly class ActivateProductPrice
{
    public function __construct(public ProductPriceId $id, public ActorContext $actor) {}
}
final readonly class DeactivateProductPrice
{
    public function __construct(public ProductPriceId $id, public ActorContext $actor) {}
}
final readonly class ArchiveProductPrice
{
    public function __construct(public ProductPriceId $id, public ActorContext $actor) {}
}
