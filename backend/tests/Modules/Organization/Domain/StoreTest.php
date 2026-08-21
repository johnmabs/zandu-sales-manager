<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Event\StoreClosureCancelled;
use Zandu\Modules\Organization\Domain\Store\Event\StoreClosureRequested;
use Zandu\Modules\Organization\Domain\Store\Event\StoreCreated;
use Zandu\Modules\Organization\Domain\Store\Event\StoreReactivated;
use Zandu\Modules\Organization\Domain\Store\Event\StoreSuspended;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\Store\StoreStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Money\Currency;

final class StoreTest extends TestCase
{
    private const STORE_ID = '0198d22a-e5fc-7416-85e6-2e65291fa9b4';
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    public function testValueObjectsNormalizeTheirValues(): void
    {
        self::assertSame('BZV-CENTRE', StoreCode::fromString('bzv-centre')->value());
        self::assertSame('Brazzaville Centre', StoreName::fromString(' Brazzaville   Centre ')->value());
        self::assertSame('12 avenue de la Paix', StoreAddress::fromString(' 12  avenue de la Paix ')->value());
    }

    #[DataProvider('invalidValueObjects')]
    public function testValueObjectsRejectInvalidValues(callable $operation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $operation();
    }

    /** @return iterable<string, array{callable(): object}> */
    public static function invalidValueObjects(): iterable
    {
        yield 'code' => [static fn(): StoreCode => StoreCode::fromString('!')];
        yield 'name' => [static fn(): StoreName => StoreName::fromString('A')];
        yield 'address' => [static fn(): StoreAddress => StoreAddress::fromString('')];
    }

    public function testStoreIsCreatedActiveWithImmutableTenantAndCode(): void
    {
        $store = $this->store();

        self::assertSame(StoreStatus::Active, $store->status());
        self::assertSame(self::ORGANIZATION_ID, $store->organizationId()->toString());
        self::assertSame('BZV-CENTRE', $store->code()->value());
        self::assertSame('UTC', $store->createdAt()->getTimezone()->getName());
        self::assertInstanceOf(StoreCreated::class, $store->releaseEvents()[0]);
    }

    public function testCurrencyMustMatchOrganizationDefault(): void
    {
        $this->expectException(LogicException::class);
        $this->store(Currency::fromCode('USD'));
    }

    public function testSuspensionReactivationAndClosureCancellationFollowLifecycle(): void
    {
        $store = $this->store();
        $store->releaseEvents();
        $at = new DateTimeImmutable('2026-08-22T12:00:00+01:00');

        $store->suspend($this->actorId(), $at);
        self::assertSame(StoreStatus::Suspended, $store->status());
        self::assertInstanceOf(StoreSuspended::class, $store->releaseEvents()[0]);

        $store->reactivate($this->actorId(), $at);
        self::assertSame(StoreStatus::Active, $store->status());
        self::assertInstanceOf(StoreReactivated::class, $store->releaseEvents()[0]);

        $store->requestClosure($this->actorId(), $at);
        self::assertSame(StoreStatus::ClosurePending, $store->status());
        self::assertInstanceOf(StoreClosureRequested::class, $store->releaseEvents()[0]);

        $store->cancelClosure($this->actorId(), $at);
        self::assertSame(StoreStatus::Active, $store->status());
        self::assertInstanceOf(StoreClosureCancelled::class, $store->releaseEvents()[0]);
        self::assertSame(5, $store->version());
    }

    public function testClosedWorkflowCannotBeReactivatedByLifecycleOperations(): void
    {
        $store = $this->store();
        $store->requestClosure($this->actorId(), new DateTimeImmutable('2026-08-22T12:00:00+00:00'));

        $this->expectException(LogicException::class);
        $store->reactivate($this->actorId(), new DateTimeImmutable('2026-08-22T13:00:00+00:00'));
    }

    private function store(?Currency $organizationCurrency = null): Store
    {
        $factory = new SymfonyUuidFactory();

        return Store::create(
            StoreId::fromString(self::STORE_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            StoreCode::fromString('BZV-CENTRE'),
            StoreName::fromString('Brazzaville Centre'),
            StoreAddress::fromString('12 avenue de la Paix'),
            TimeZone::fromString('Africa/Brazzaville'),
            Currency::fromCode('XAF'),
            Locale::fromString('fr_CG'),
            $organizationCurrency ?? Currency::fromCode('XAF'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T09:00:00+01:00'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
