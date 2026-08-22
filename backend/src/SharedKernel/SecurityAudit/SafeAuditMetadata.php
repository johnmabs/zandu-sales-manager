<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

use InvalidArgumentException;

final readonly class SafeAuditMetadata
{
    private const SENSITIVE_KEY_PARTS = [
        'password',
        'secret',
        'token',
        'authorization',
        'cookie',
        'credential',
    ];

    /** @param array<string, bool|float|int|string|null> $values */
    private function __construct(private array $values) {}

    /** @param array<string, bool|float|int|string|null> $values */
    public static function fromArray(array $values): self
    {
        foreach ($values as $key => $value) {
            $normalizedKey = strtolower($key);
            foreach (self::SENSITIVE_KEY_PARTS as $sensitivePart) {
                if (str_contains($normalizedKey, $sensitivePart)) {
                    throw new InvalidArgumentException(sprintf('Sensitive audit metadata key "%s" is forbidden.', $key));
                }
            }
            if (is_string($value) && strlen($value) > 512) {
                throw new InvalidArgumentException(sprintf('Audit metadata value "%s" is too long.', $key));
            }
        }

        return new self($values);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** @return array<string, bool|float|int|string|null> */
    public function toArray(): array
    {
        return $this->values;
    }
}
