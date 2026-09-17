<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\State;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Api\State\OpaqueCursor;

final class OpaqueCursorTest extends TestCase
{
    public function testItRoundTripsAnIdentifierWithoutExposingJson(): void
    {
        $id = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
        $cursor = OpaqueCursor::encode($id);

        self::assertSame($id, OpaqueCursor::decode($cursor));
        self::assertStringNotContainsString($id, $cursor);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCursors(): iterable
    {
        yield 'not base64url' => ['***'];
        yield 'not json' => ['bm90LWpzb24'];
        yield 'missing id' => ['e30'];
        yield 'extra state' => ['eyJpZCI6ImEiLCJwYWdlIjoyfQ'];
    }

    #[DataProvider('invalidCursors')]
    public function testItRejectsInvalidCursors(string $cursor): void
    {
        $this->expectException(InvalidArgumentException::class);

        OpaqueCursor::decode($cursor);
    }
}
