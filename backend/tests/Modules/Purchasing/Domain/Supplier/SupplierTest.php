<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Domain\Supplier;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierArchived;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierCreated;
use Zandu\Modules\Purchasing\Domain\Supplier\Event\SupplierUpdated;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierName;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierStatus;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SupplierId;

final class SupplierTest extends TestCase
{
    private const SUPPLIER_ID = '0198d901-1111-7000-8000-000000000001';
    private const ORGANIZATION_ID = '0198d901-1111-7000-8000-000000000002';
    private const ACTOR_ID = '0198d901-1111-7000-8000-000000000003';

    public function testCreationNormalizesOptionalCoordinatesAndRecordsAnEvent(): void
    {
        $supplier = $this->supplier();
        $events = $supplier->releaseEvents();

        self::assertSame('Acme Distribution', $supplier->name()->value());
        self::assertSame('+243 999 000 000', $supplier->phone());
        self::assertSame('sales@acme.example', $supplier->email());
        self::assertNull($supplier->address());
        self::assertSame(SupplierStatus::Active, $supplier->status());
        self::assertSame(1, $supplier->version());
        self::assertInstanceOf(SupplierCreated::class, $events[0]);
        self::assertSame('UTC', $events[0]->occurredAt()->getTimezone()->getName());
    }

    public function testUpdateAndLifecycleMaintainAuditAndOptimisticVersion(): void
    {
        $supplier = $this->supplier();
        $supplier->releaseEvents();
        $at = new DateTimeImmutable('2026-08-28T19:00:00+01:00');

        $supplier->update(SupplierName::fromString('Acme RDC'), null, null, 'Kinshasa', 'Priority', $this->actorId(), $at);
        self::assertSame(2, $supplier->version());
        self::assertSame('Acme RDC', $supplier->name()->value());
        self::assertInstanceOf(SupplierUpdated::class, $supplier->releaseEvents()[0]);

        $supplier->deactivate($this->actorId(), $at);
        $supplier->activate($this->actorId(), $at);
        $supplier->archive($this->actorId(), $at);

        self::assertSame(SupplierStatus::Archived, $supplier->status());
        self::assertSame(5, $supplier->version());
        self::assertSame(self::ACTOR_ID, $supplier->updatedBy()?->toString());
        self::assertSame('UTC', $supplier->updatedAt()?->getTimezone()->getName());
        self::assertInstanceOf(SupplierArchived::class, array_slice($supplier->releaseEvents(), -1)[0]);
    }

    public function testArchivedSupplierCannotBeUpdatedOrReactivated(): void
    {
        $supplier = $this->supplier();
        $supplier->archive($this->actorId(), new DateTimeImmutable('2026-08-28T20:00:00Z'));

        try {
            $supplier->update(SupplierName::fromString('Nope'), null, null, null, null, $this->actorId(), new DateTimeImmutable());
            self::fail('An archived supplier must remain immutable.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('SUPPLIER_ARCHIVED', $exception->errorCode());
        }

        $this->expectException(PurchasingRuleViolation::class);
        $supplier->activate($this->actorId(), new DateTimeImmutable());
    }

    #[DataProvider('invalidSupplierData')]
    public function testInvalidDataIsRejected(callable $operation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $operation();
    }

    /** @return iterable<string, array{callable(): mixed}> */
    public static function invalidSupplierData(): iterable
    {
        yield 'empty name' => [static fn() => SupplierName::fromString('  ')];
        yield 'long name' => [static fn() => SupplierName::fromString(str_repeat('a', 161))];
        yield 'invalid email' => [static fn() => self::createSupplier('invalid')];
    }

    private function supplier(): Supplier
    {
        return self::createSupplier(' Sales@Acme.Example ');
    }

    private static function createSupplier(?string $email): Supplier
    {
        $factory = new SymfonyUuidFactory();

        return Supplier::create(
            SupplierId::fromString(self::SUPPLIER_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            SupplierName::fromString(' Acme   Distribution '),
            ' +243 999 000 000 ',
            $email,
            ' ',
            null,
            ActorId::fromString(self::ACTOR_ID, $factory),
            new DateTimeImmutable('2026-08-28T18:00:00+01:00'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
