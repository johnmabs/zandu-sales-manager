<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Versioning;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\SharedKernel\Versioning\AggregateVersionMismatch;
use Zandu\SharedKernel\Versioning\ExpectedVersion;
use Zandu\SharedKernel\Versioning\TracksAggregateVersion;
use Zandu\SharedKernel\Versioning\VersionedAggregate;

final class ExpectedVersionTest extends TestCase
{
    public function testItAcceptsTheCurrentAggregateVersion(): void
    {
        ExpectedVersion::fromInt(2)->assertMatches(TestVersionedAggregate::atVersion(2));

        self::assertSame(2, ExpectedVersion::fromInt(2)->toInt());
    }

    public function testItRejectsAStaleAggregateVersion(): void
    {
        $this->expectException(AggregateVersionMismatch::class);

        ExpectedVersion::fromInt(1)->assertMatches(TestVersionedAggregate::atVersion(2));
    }

    #[DataProvider('invalidVersions')]
    public function testItRejectsNonPositiveVersions(int $version): void
    {
        $this->expectException(InvalidArgumentException::class);

        ExpectedVersion::fromInt($version);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidVersions(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }
}

final class TestVersionedAggregate implements VersionedAggregate
{
    use TracksAggregateVersion;

    private function __construct(int $version)
    {
        $this->version = $version;
    }

    public static function atVersion(int $version): self
    {
        return new self($version);
    }
}
