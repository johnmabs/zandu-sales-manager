<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Versioning;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class TracksAggregateVersionTest extends TestCase
{
    public function testItExposesAndAdvancesAPositiveVersion(): void
    {
        $aggregate = new VersionedAggregateStub(4);

        self::assertSame(4, $aggregate->version());
        $aggregate->change();
        self::assertSame(5, $aggregate->version());
    }

    public function testItRejectsAnInvalidVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate version must be positive.');

        new VersionedAggregateStub(0);
    }
}

final class VersionedAggregateStub implements VersionedAggregate
{
    use TracksAggregateVersion;

    public function __construct(private int $version)
    {
        $this->assertValidVersion();
    }

    public function change(): void
    {
        $this->advanceVersion();
    }
}
