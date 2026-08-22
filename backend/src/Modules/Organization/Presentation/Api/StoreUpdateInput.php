<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

final readonly class StoreUpdateInput
{
    public function __construct(
        public string $name,
        public ?string $address,
        public string $timeZone,
        public string $locale,
    ) {}
}
