<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Identity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Identity\SymfonyUuidV7Generator;

final class SymfonyUuidTest extends TestCase
{
    private const UUID_V7 = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testFactoryReconstructsUuidV7(): void
    {
        $uuid = (new SymfonyUuidFactory())->fromString(self::UUID_V7);

        self::assertSame(self::UUID_V7, $uuid->toString());
        self::assertTrue($uuid->equals((new SymfonyUuidFactory())->fromString(self::UUID_V7)));
    }

    public function testFactoryRejectsMalformedUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a valid UUID v7.');

        (new SymfonyUuidFactory())->fromString('not-a-uuid');
    }

    public function testFactoryRejectsAnotherUuidVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a valid UUID v7.');

        (new SymfonyUuidFactory())->fromString('6ba7b810-9dad-41d1-80b4-00c04fd430c8');
    }

    public function testGeneratorCreatesUuidV7(): void
    {
        $uuid = (new SymfonyUuidV7Generator())->generate();

        self::assertTrue(UuidV7::isValid($uuid->toString()));
        self::assertSame('7', $uuid->toString()[14]);
    }

    public function testGeneratorCreatesDistinctIdentifiers(): void
    {
        $generator = new SymfonyUuidV7Generator();

        self::assertFalse($generator->generate()->equals($generator->generate()));
    }
}
