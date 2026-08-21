<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use InvalidArgumentException;

final readonly class InvitationEmail
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));
        if (false === filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 254) {
            throw new InvalidArgumentException('A valid invitation email is required.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
