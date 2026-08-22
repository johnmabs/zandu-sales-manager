<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

use InvalidArgumentException;
use Zandu\SharedKernel\Identity\TypedId;

final readonly class ResourceReference
{
    private function __construct(public string $type, public string $id) {}

    public static function for(string $type, TypedId $id): self
    {
        $normalizedType = strtoupper(trim($type));
        if (1 !== preg_match('/^[A-Z][A-Z0-9_]{1,63}$/', $normalizedType)) {
            throw new InvalidArgumentException('Audit resource type must be a stable uppercase identifier.');
        }

        return new self($normalizedType, $id->toString());
    }
}
