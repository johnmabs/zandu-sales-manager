<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Error;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\SharedKernel\Error\DomainError;
use Zandu\SharedKernel\Error\Result;

final class ResultTest extends TestCase
{
    public function testSuccessCarriesItsValue(): void
    {
        $result = Result::success(['saleId' => 'sale-42']);

        self::assertTrue($result->isSuccess());
        self::assertFalse($result->isFailure());
        self::assertSame(['saleId' => 'sale-42'], $result->value());
    }

    public function testFailureCarriesADomainError(): void
    {
        $error = DomainError::create('SALE_ALREADY_COMPLETED', 'The sale is already completed.');
        $result = Result::failure($error);

        self::assertTrue($result->isFailure());
        self::assertFalse($result->isSuccess());
        self::assertSame($error, $result->error());
    }

    public function testFailureDoesNotExposeAValue(): void
    {
        $result = Result::failure(DomainError::create('SALE_INVALID', 'The sale is invalid.'));

        $this->expectException(LogicException::class);

        $result->value();
    }

    public function testSuccessDoesNotExposeAnError(): void
    {
        $result = Result::success(null);

        $this->expectException(LogicException::class);

        $result->error();
    }

    public function testItIsImmutable(): void
    {
        self::assertTrue((new ReflectionClass(Result::class))->isReadOnly());
    }
}
