<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

final readonly class StoreCreateInput
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $address,
        public string $timeZone,
        public string $currency,
        public string $locale,
    ) {}
}
