<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Error;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\SharedKernel\Error\DomainError;

final class DomainErrorTest extends TestCase
{
    public function testItCarriesAStableCodeAndHumanReadableMessage(): void
    {
        $error = DomainError::create('SALE_ALREADY_COMPLETED', 'The sale is already completed.');

        self::assertSame('SALE_ALREADY_COMPLETED', $error->code());
        self::assertSame('The sale is already completed.', $error->message());
        self::assertTrue((new ReflectionClass(DomainError::class))->isReadOnly());
    }

    #[DataProvider('invalidCodes')]
    public function testItRejectsAnUnstableCode(string $code): void
    {
        $this->expectException(InvalidArgumentException::class);

        DomainError::create($code, 'A useful message.');
    }

    public function testItRejectsABlankMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DomainError::create('SALE_INVALID', " \t\n");
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'lowercase' => ['sale_invalid'];
        yield 'space' => ['SALE INVALID'];
        yield 'leading digit' => ['1_SALE_INVALID'];
        yield 'repeated separator' => ['SALE__INVALID'];
    }
}
