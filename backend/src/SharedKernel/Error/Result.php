<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Error;

use LogicException;

/**
 * @template T
 */
final readonly class Result
{
    private function __construct(
        private bool $successful,
        private mixed $value,
        private ?DomainError $error,
    ) {}

    /**
     * @template TValue
     *
     * @param TValue $value
     *
     * @return self<TValue>
     */
    public static function success(mixed $value): self
    {
        return new self(true, $value, null);
    }

    /**
     * @return self<never>
     */
    public static function failure(DomainError $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->successful;
    }

    public function isFailure(): bool
    {
        return !$this->successful;
    }

    /**
     * @return T
     */
    public function value(): mixed
    {
        if ($this->isFailure()) {
            throw new LogicException('A failed result has no value.');
        }

        return $this->value;
    }

    public function error(): DomainError
    {
        if ($this->isSuccess()) {
            throw new LogicException('A successful result has no error.');
        }

        return $this->error;
    }
}
