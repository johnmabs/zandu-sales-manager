<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\DeactivateSupplier;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class DeactivateSupplier
{
    public function __construct(public SupplierId $supplierId, public ActorContext $actorContext) {}
}
