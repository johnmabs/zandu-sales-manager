<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\UpdateSupplier;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class UpdateSupplier
{
    public function __construct(
        public SupplierId $supplierId,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
        public ActorContext $actorContext,
    ) {}
}
