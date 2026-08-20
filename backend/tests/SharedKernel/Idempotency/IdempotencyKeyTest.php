<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Idempotency;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;

final class IdempotencyKeyTest extends TestCase
{
    public function testItPreservesAnOpaqueValue(): void
    {
        $key = IdempotencyKey::fromString('checkout/client-generated-key_42');

        self::assertSame('checkout/client-generated-key_42', $key->toString());
    }

    public function testEqualityRequiresTheSameExactValue(): void
    {
        $key = IdempotencyKey::fromString('checkout-42');

        self::assertTrue($key->equals(IdempotencyKey::fromString('checkout-42')));
        self::assertFalse($key->equals(IdempotencyKey::fromString('CHECKOUT-42')));
    }

    #[DataProvider('invalidValues')]
    public function testItRejectsAnInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        IdempotencyKey::fromString($value);
    }

    public function testItAcceptsTheMaximumLength(): void
    {
        $value = str_repeat('a', IdempotencyKey::MAX_LENGTH);

        self::assertSame($value, IdempotencyKey::fromString($value)->toString());
    }

    public function testItIsImmutable(): void
    {
        self::assertTrue((new ReflectionClass(IdempotencyKey::class))->isReadOnly());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => [" \t\n"];
        yield 'too long' => [str_repeat('a', IdempotencyKey::MAX_LENGTH + 1)];
    }
}
