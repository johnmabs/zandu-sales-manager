<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateSupplier;

use Zandu\SharedKernel\Context\ActorContext;

final readonly class CreateSupplier
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
        public ActorContext $actorContext,
    ) {}
}
