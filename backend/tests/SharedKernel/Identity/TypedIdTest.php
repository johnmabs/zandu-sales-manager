<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Identity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\SharedKernel\Identity\CashSessionId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\SaleId;
use Zandu\SharedKernel\Identity\StockId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Identity\TypedId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Identity\UuidFactory;

final class TypedIdTest extends TestCase
{
    private const UUID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    /**
     * @param class-string<TypedId> $idClass
     */
    #[DataProvider('idClasses')]
    public function testTypedIdCanBeReconstructed(string $idClass): void
    {
        $id = $idClass::fromString(self::UUID, new TypedIdUuidFactory());

        self::assertInstanceOf($idClass, $id);
        self::assertSame(self::UUID, $id->toString());
    }

    /**
     * @param class-string<TypedId> $idClass
     */
    #[DataProvider('idClasses')]
    public function testTypedIdCanBeGenerated(string $idClass): void
    {
        $id = $idClass::generate(new TypedIdGenerator());

        self::assertInstanceOf($idClass, $id);
        self::assertSame(self::UUID, $id->toString());
    }

    public function testEqualityRequiresTheSameTypeAndValue(): void
    {
        $factory = new TypedIdUuidFactory();
        $saleId = SaleId::fromString(self::UUID, $factory);

        self::assertTrue($saleId->equals(SaleId::fromString(self::UUID, $factory)));
        self::assertFalse($saleId->equals(ProductId::fromString(self::UUID, $factory)));
        self::assertFalse($saleId->equals(SaleId::fromString(
            '0198c728-a648-75b7-b7d7-c69d0bf84390',
            $factory,
        )));
    }

    /**
     * @return iterable<string, array{class-string<TypedId>}>
     */
    public static function idClasses(): iterable
    {
        yield 'organization' => [OrganizationId::class];
        yield 'store' => [StoreId::class];
        yield 'product' => [ProductId::class];
        yield 'sale' => [SaleId::class];
        yield 'stock' => [StockId::class];
        yield 'cash session' => [CashSessionId::class];
        yield 'supplier' => [SupplierId::class];
    }
}

final readonly class TypedIdUuid implements Uuid
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

final class TypedIdUuidFactory implements UuidFactory
{
    public function fromString(string $value): Uuid
    {
        return new TypedIdUuid($value);
    }
}

final class TypedIdGenerator implements IdGenerator
{
    private const UUID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function generate(): Uuid
    {
        return new TypedIdUuid(self::UUID);
    }
}
