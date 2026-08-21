<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\UpdateStore;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class UpdateStore
{
    public function __construct(
        public StoreId $storeId,
        public string $name,
        public ?string $address,
        public string $timeZone,
        public string $locale,
        public ActorContext $actorContext,
    ) {}
}
