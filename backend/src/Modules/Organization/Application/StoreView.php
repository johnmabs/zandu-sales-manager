<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

final readonly class StoreView
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $code,
        public string $name,
        public string $status,
        public ?string $address,
        public string $timeZone,
        public string $currency,
        public string $locale,
        public string $updatedAt,
        public int $version,
    ) {}
}
