<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Messaging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\TypedId;
use Zandu\SharedKernel\Messaging\CausationId;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class MessageCausalityIdTest extends TestCase
{
    private const UUID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    /**
     * @param class-string<TypedId> $idClass
     */
    #[DataProvider('causalityIdClasses')]
    public function testCausalityIdCanBeReconstructed(string $idClass): void
    {
        $id = $idClass::fromString(self::UUID, new SymfonyUuidFactory());

        self::assertSame(self::UUID, $id->toString());
        self::assertTrue((new ReflectionClass($idClass))->isReadOnly());
    }

    public function testCorrelationAndCausationRemainDistinctConcepts(): void
    {
        $factory = new SymfonyUuidFactory();
        $correlationId = CorrelationId::fromString(self::UUID, $factory);
        $causationId = CausationId::fromString(self::UUID, $factory);

        self::assertTrue($correlationId->equals(CorrelationId::fromString(self::UUID, $factory)));
        self::assertTrue($causationId->equals(CausationId::fromString(self::UUID, $factory)));
        self::assertFalse($correlationId->equals($causationId));
        self::assertFalse($causationId->equals($correlationId));
    }

    /**
     * @return iterable<string, array{class-string<TypedId>}>
     */
    public static function causalityIdClasses(): iterable
    {
        yield 'correlation' => [CorrelationId::class];
        yield 'causation' => [CausationId::class];
    }
}
