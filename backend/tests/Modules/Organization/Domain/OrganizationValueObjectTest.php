<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\TimeZone;

final class OrganizationValueObjectTest extends TestCase
{
    public function testValuesAreNormalizedAndExposed(): void
    {
        self::assertSame('Zandu Sales', OrganizationName::fromString('  Zandu   Sales  ')->value());
        self::assertSame('CG', CountryCode::fromString('cg')->value());
        self::assertSame('fr_CG', Locale::fromString('fr_CG')->value());
        self::assertSame('Africa/Brazzaville', TimeZone::fromString('Africa/Brazzaville')->value());
    }

    #[DataProvider('invalidValues')]
    public function testInvalidValuesAreRejected(callable $operation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $operation();
    }

    /** @return iterable<string, array{callable(): object}> */
    public static function invalidValues(): iterable
    {
        yield 'short name' => [static fn(): OrganizationName => OrganizationName::fromString('A')];
        yield 'long name' => [static fn(): OrganizationName => OrganizationName::fromString(str_repeat('A', 161))];
        yield 'country' => [static fn(): CountryCode => CountryCode::fromString('COG')];
        yield 'locale' => [static fn(): Locale => Locale::fromString('fr-cg')];
        yield 'timezone' => [static fn(): TimeZone => TimeZone::fromString('Africa/Unknown')];
    }
}
