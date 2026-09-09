<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\ApiProperty;

final readonly class EffectiveAccessResource
{
    /**
     * @param list<string> $permissions
     * @param list<string> $accessibleStoreIds
     */
    public function __construct(
        public string $organizationId,
        public int $authorizationVersion,
        #[ApiProperty(openapiContext: ['type' => 'array', 'items' => ['type' => 'string']])]
        public array $permissions,
        #[ApiProperty(openapiContext: [
            'type' => 'object',
            'required' => ['type'],
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['ORGANIZATION', 'SELECTED_STORES']],
                'storeIds' => ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'uuid']],
            ],
        ])]
        public EffectiveAccessScopeResource $scope,
        #[ApiProperty(openapiContext: ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'uuid']])]
        public array $accessibleStoreIds,
    ) {}
}
