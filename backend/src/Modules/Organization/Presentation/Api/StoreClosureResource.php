<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

final readonly class StoreClosureResource
{
    /** @param list<string> $blockers */
    public function __construct(
        public string $id,
        public string $storeId,
        public string $status,
        public string $reason,
        public array $blockers,
        public string $requestedAt,
        public int $version,
    ) {}
}
