<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\CreateStore;

use Zandu\SharedKernel\Context\ActorContext;

final readonly class CreateStore
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $address,
        public string $timeZone,
        public string $currency,
        public string $locale,
        public ActorContext $actorContext,
    ) {}
}
