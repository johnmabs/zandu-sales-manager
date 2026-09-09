<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

final readonly class EffectiveAccessView
{
    /**
     * @param list<string> $permissions
     * @param list<string> $accessibleStoreIds
     * @param array{type: string, storeIds?: list<string>} $scope
     */
    public function __construct(
        public string $organizationId,
        public int $authorizationVersion,
        public array $permissions,
        public array $scope,
        public array $accessibleStoreIds,
    ) {}
}
