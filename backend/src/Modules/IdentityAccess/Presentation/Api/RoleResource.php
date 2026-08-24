<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(operations: [
    new GetCollection(name: 'role_list', uriTemplate: '/roles', provider: RoleProvider::class),
])]
final readonly class RoleResource
{
    /** @param non-empty-list<string> $permissions */
    public function __construct(
        public string $id,
        public string $code,
        public string $type,
        public string $status,
        public string $name,
        public ?string $description,
        public array $permissions,
        public int $version,
    ) {}
}
