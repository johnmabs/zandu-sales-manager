<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\CreateOrganization;

use Zandu\SharedKernel\Context\ActorContext;

final readonly class CreateOrganization
{
    public function __construct(
        public string $name,
        public string $countryCode,
        public string $defaultCurrency,
        public string $defaultTimeZone,
        public string $defaultLocale,
        public ActorContext $actorContext,
    ) {}
}
