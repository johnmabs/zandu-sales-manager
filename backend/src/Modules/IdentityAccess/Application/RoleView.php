<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

final readonly class RoleView
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
