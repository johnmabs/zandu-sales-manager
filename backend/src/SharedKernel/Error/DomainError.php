<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Error;

use InvalidArgumentException;

final readonly class DomainError
{
    private function __construct(
        private string $code,
        private string $message,
    ) {}

    public static function create(string $code, string $message): self
    {
        if (1 !== preg_match('/^[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)*$/', $code)) {
            throw new InvalidArgumentException('Domain error code must be a stable uppercase snake-case identifier.');
        }

        if ('' === trim($message)) {
            throw new InvalidArgumentException('Domain error message must not be empty.');
        }

        return new self($code, $message);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }
}
