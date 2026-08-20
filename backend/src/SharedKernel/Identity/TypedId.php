<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Identity;

abstract readonly class TypedId
{
    final public function __construct(private Uuid $uuid)
    {
    }

    final public static function fromString(string $value, UuidFactory $factory): static
    {
        return new static($factory->fromString($value));
    }

    final public static function generate(IdGenerator $generator): static
    {
        return new static($generator->generate());
    }

    final public function equals(self $other): bool
    {
        return $this::class === $other::class && $this->uuid->equals($other->uuid);
    }

    final public function toString(): string
    {
        return $this->uuid->toString();
    }
}
