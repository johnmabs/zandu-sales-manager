<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

final readonly class OrganizationView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public string $countryCode,
        public string $defaultCurrency,
        public string $defaultTimeZone,
        public string $defaultLocale,
        public string $updatedAt,
        public int $version,
    ) {}
}
