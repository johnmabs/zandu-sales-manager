<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Identity;

use PHPUnit\Framework\TestCase;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Identity\UuidFactory;

final class IdentityContractsTest extends TestCase
{
    private const UUID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testUuidCanBeCreatedThroughTheSharedFactoryContract(): void
    {
        $factory = new InMemoryUuidFactory();

        $uuid = $factory->fromString(self::UUID);

        self::assertSame(self::UUID, $uuid->toString());
    }

    public function testIdCanBeGeneratedThroughTheSharedGeneratorContract(): void
    {
        $generator = new FixedIdGenerator(new InMemoryUuid(self::UUID));

        $uuid = $generator->generate();

        self::assertSame(self::UUID, $uuid->toString());
    }

    public function testUuidEqualityIsValueBased(): void
    {
        $uuid = new InMemoryUuid(self::UUID);

        self::assertTrue($uuid->equals(new InMemoryUuid(self::UUID)));
        self::assertFalse($uuid->equals(new InMemoryUuid('0198c728-a648-75b7-b7d7-c69d0bf84390')));
    }
}

final readonly class InMemoryUuid implements Uuid
{
    public function __construct(private string $value) {}

    public function equals(Uuid $other): bool
    {
        return $this->value === $other->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}

final class InMemoryUuidFactory implements UuidFactory
{
    public function fromString(string $value): Uuid
    {
        return new InMemoryUuid($value);
    }
}

final readonly class FixedIdGenerator implements IdGenerator
{
    public function __construct(private Uuid $uuid) {}

    public function generate(): Uuid
    {
        return $this->uuid;
    }
}
