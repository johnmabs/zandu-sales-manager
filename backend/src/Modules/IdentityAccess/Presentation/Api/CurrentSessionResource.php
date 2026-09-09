<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(operations: [
    new Get(name: 'current_session', uriTemplate: '/session', provider: CurrentSessionProvider::class),
])]
final readonly class CurrentSessionResource
{
    /** @var list<CurrentOrganizationResource> */
    #[ApiProperty(openapiContext: [
        'type' => 'array',
        'items' => [
            'type' => 'object',
            'required' => ['id', 'name', 'status', 'defaultCurrency', 'defaultTimeZone', 'defaultLocale'],
            'properties' => [
                'id' => ['type' => 'string', 'format' => 'uuid'],
                'name' => ['type' => 'string'],
                'status' => ['type' => 'string', 'enum' => ['ACTIVE', 'SUSPENDED', 'CLOSURE_PENDING', 'CLOSED']],
                'defaultCurrency' => ['type' => 'string'],
                'defaultTimeZone' => ['type' => 'string'],
                'defaultLocale' => ['type' => 'string'],
            ],
        ],
    ])]
    public array $organizations;

    /** @param list<CurrentOrganizationResource> $organizations */
    public function __construct(
        public string $id,
        public string $userId,
        public string $organizationId,
        public int $authorizationVersion,
        public EffectiveAccessResource $effectiveAccess,
        public ?string $email,
        array $organizations,
    ) {
        $this->organizations = $organizations;
    }
}
