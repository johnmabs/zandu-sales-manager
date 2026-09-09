<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

final readonly class EffectiveAccessScopeResource
{
    /** @param list<string> $storeIds */
    public function __construct(
        public string $type,
        public array $storeIds = [],
    ) {}
}
