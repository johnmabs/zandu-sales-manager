<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

final readonly class OrganizationSummary
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public string $defaultCurrency,
        public string $defaultTimeZone,
        public string $defaultLocale,
    ) {}
}
