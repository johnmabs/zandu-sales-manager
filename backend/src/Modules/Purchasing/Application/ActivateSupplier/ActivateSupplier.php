<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ActivateSupplier;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class ActivateSupplier
{
    public function __construct(public SupplierId $supplierId, public ActorContext $actorContext) {}
}
