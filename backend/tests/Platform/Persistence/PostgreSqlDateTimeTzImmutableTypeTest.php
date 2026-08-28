<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Persistence;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Persistence\PostgreSqlDateTimeTzImmutableType;

final class PostgreSqlDateTimeTzImmutableTypeTest extends TestCase
{
    public function testItReadsPostgreSqlTimestampsWithMicroseconds(): void
    {
        $date = (new PostgreSqlDateTimeTzImmutableType())->convertToPHPValue(
            '2026-08-28 12:46:00.357814+00',
            new PostgreSQLPlatform(),
        );

        self::assertNotNull($date);
        self::assertSame('2026-08-28T12:46:00.357814+00:00', $date->format('Y-m-d\TH:i:s.uP'));
    }
}
