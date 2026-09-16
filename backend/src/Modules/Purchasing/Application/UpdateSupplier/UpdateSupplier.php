<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\UpdateSupplier;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

final readonly class UpdateSupplier
{
    public function __construct(
        public SupplierId $supplierId,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
        public ExpectedVersion $expectedVersion,
        public ActorContext $actorContext,
    ) {}
}
