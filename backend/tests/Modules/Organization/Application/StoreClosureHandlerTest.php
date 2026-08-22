<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Application\CancelStoreClosure\CancelStoreClosure;
use Zandu\Modules\Organization\Application\CancelStoreClosure\CancelStoreClosureHandler;
use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\Modules\Organization\Application\RequestStoreClosure\RequestStoreClosure;
use Zandu\Modules\Organization\Application\RequestStoreClosure\RequestStoreClosureHandler;
use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\Store\StoreNotFound;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\Store\StoreStatus;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosure;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureNotFound;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureRepository;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class StoreClosureHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const STORE_ID = '0198d233-147c-72d5-b75a-a936797ff9c8';
    private const CLOSURE_ID = '0198d255-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    public function testRequestEvaluatesOperationalBlockers(): void
    {
        $stores = new ClosureTestStoreRepository($this->store());
        $closures = new ClosureTestRepository();
        $provider = new class implements StoreClosureBlockerProvider {
            public function blockers(OrganizationId $organizationId, StoreId $storeId): array
            {
                return ['OPEN_CASH_SESSION', 'STOCK_REMAINING'];
            }
        };
        $handler = new RequestStoreClosureHandler(
            new TenantStoreLoader($stores),
            $stores,
            $closures,
            $provider,
            $this->idGenerator(),
            new FrozenClock(new DateTimeImmutable('2026-08-22T10:00:00+00:00')),
            new InMemoryTenantTransaction(),
            new AllowAllAuthorizationService(),
            new AllowAllOperationalGuard(),
        );

        $closure = $handler(new RequestStoreClosure($this->storeId(), 'Fin du bail', $this->context()));

        self::assertSame(StoreClosureStatus::InProgress, $closure->status());
        self::assertSame(['OPEN_CASH_SESSION', 'STOCK_REMAINING'], $closure->blockers());
        self::assertSame(StoreStatus::ClosurePending, $stores->store->status());
    }

    public function testRequestWithoutBlockersIsReadyAndCanBeCancelled(): void
    {
        $stores = new ClosureTestStoreRepository($this->store());
        $closures = new ClosureTestRepository();
        $provider = new class implements StoreClosureBlockerProvider {
            public function blockers(OrganizationId $organizationId, StoreId $storeId): array
            {
                return [];
            }
        };
        $clock = new FrozenClock(new DateTimeImmutable('2026-08-22T10:00:00+00:00'));
        $transaction = new InMemoryTenantTransaction();
        $request = new RequestStoreClosureHandler(
            new TenantStoreLoader($stores),
            $stores,
            $closures,
            $provider,
            $this->idGenerator(),
            $clock,
            $transaction,
            new AllowAllAuthorizationService(),
            new AllowAllOperationalGuard(),
        );
        $closure = $request(new RequestStoreClosure($this->storeId(), 'Regroupement', $this->context()));
        self::assertSame(StoreClosureStatus::Ready, $closure->status());

        $cancel = new CancelStoreClosureHandler(new TenantStoreLoader($stores), $stores, $closures, $clock, $transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard());
        $store = $cancel(new CancelStoreClosure($this->storeId(), $this->context()));

        self::assertSame(StoreClosureStatus::Cancelled, $closure->status());
        self::assertSame(StoreStatus::Active, $store->status());
    }

    private function store(): Store
    {
        return Store::create(
            $this->storeId(),
            $this->organizationId(),
            StoreCode::fromString('CENTRE'),
            StoreName::fromString('Centre'),
            StoreAddress::fromString('Brazzaville'),
            TimeZone::fromString('Africa/Brazzaville'),
            Currency::fromCode('XAF'),
            Locale::fromString('fr_CG'),
            Currency::fromCode('XAF'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T08:00:00+00:00'),
        );
    }

    private function context(): ActorContext
    {
        return new ActorContext(
            $this->actorId(),
            $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, new SymfonyUuidFactory()),
            new DateTimeImmutable('2026-08-22T07:00:00+00:00'),
        );
    }

    private function idGenerator(): IdGenerator
    {
        $uuid = (new SymfonyUuidFactory())->fromString(self::CLOSURE_ID);
        return new class ($uuid) implements IdGenerator {
            public function __construct(private readonly Uuid $uuid) {}
            public function generate(): Uuid
            {
                return $this->uuid;
            }
        };
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory());
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString(self::STORE_ID, new SymfonyUuidFactory());
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}

final class ClosureTestStoreRepository implements StoreRepository
{
    public function __construct(public Store $store) {}
    public function save(Store $store): void
    {
        $this->store = $store;
    }
    public function get(OrganizationId $organizationId, StoreId $storeId): Store
    {
        return $this->find($organizationId, $storeId) ?? throw StoreNotFound::withId($storeId);
    }
    public function find(OrganizationId $organizationId, StoreId $storeId): ?Store
    {
        return $this->store->organizationId()->equals($organizationId) && $this->store->id()->equals($storeId) ? $this->store : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return $this->store->organizationId()->equals($organizationId) ? [$this->store] : [];
    }

    public function codeExists(OrganizationId $organizationId, StoreCode $code): bool
    {
        return false;
    }
}

final class ClosureTestRepository implements StoreClosureRepository
{
    public ?StoreClosure $closure = null;
    public function save(StoreClosure $closure): void
    {
        $this->closure = $closure;
    }
    public function getActiveForStore(OrganizationId $organizationId, StoreId $storeId): StoreClosure
    {
        return $this->closure ?? throw StoreClosureNotFound::activeForStore($storeId);
    }
}
