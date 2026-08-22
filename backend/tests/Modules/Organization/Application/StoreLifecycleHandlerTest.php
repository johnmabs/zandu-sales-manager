<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Application\CreateStore\CreateStore;
use Zandu\Modules\Organization\Application\CreateStore\CreateStoreHandler;
use Zandu\Modules\Organization\Application\ReactivateStore\ReactivateStore;
use Zandu\Modules\Organization\Application\ReactivateStore\ReactivateStoreHandler;
use Zandu\Modules\Organization\Application\SuspendStore\SuspendStore;
use Zandu\Modules\Organization\Application\SuspendStore\SuspendStoreHandler;
use Zandu\Modules\Organization\Application\TenantStoreLoader;
use Zandu\Modules\Organization\Application\UpdateStore\UpdateStore;
use Zandu\Modules\Organization\Application\UpdateStore\UpdateStoreHandler;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationNotFound;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreCodeAlreadyExists;
use Zandu\Modules\Organization\Domain\Store\StoreNotFound;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\Store\StoreStatus;
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

final class StoreLifecycleHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const STORE_ID = '0198d233-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    private StoreUseCaseRepository $stores;
    private StoreUseCaseOrganizationRepository $organizations;
    private InMemoryTenantTransaction $transaction;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->stores = new StoreUseCaseRepository();
        $this->organizations = new StoreUseCaseOrganizationRepository();
        $this->organizations->save(Organization::create(
            $this->organizationId(),
            OrganizationName::fromString('Zandu'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T08:00:00+00:00'),
        ));
        $this->transaction = new InMemoryTenantTransaction();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-22T10:00:00+00:00'));
    }

    public function testItCreatesAStoreFromTheOrganizationDefaults(): void
    {
        $store = $this->createHandler()(new CreateStore(
            'centre',
            'Agence Centre',
            'Brazzaville',
            'Africa/Brazzaville',
            'XAF',
            'fr_CG',
            $this->context(),
        ));

        self::assertSame(self::STORE_ID, $store->id()->toString());
        self::assertSame('CENTRE', $store->code()->value());
        self::assertSame(StoreStatus::Active, $store->status());
        self::assertSame(self::ORGANIZATION_ID, $this->transaction->lastOrganizationId?->toString());
    }

    public function testItRejectsADuplicateCodeInsideTheTenant(): void
    {
        $handler = $this->createHandler();
        $command = new CreateStore('CENTRE', 'Centre', null, 'Africa/Brazzaville', 'XAF', 'fr_CG', $this->context());
        $handler($command);

        $this->expectException(StoreCodeAlreadyExists::class);
        $handler($command);
    }

    public function testCurrencyMustMatchTheOrganizationDefault(): void
    {
        $this->expectExceptionMessage('Store currency must match');
        $this->createHandler()(new CreateStore(
            'CENTRE',
            'Centre',
            null,
            'Africa/Brazzaville',
            'USD',
            'fr_CG',
            $this->context(),
        ));
    }

    public function testUpdateSuspendAndReactivateAreExplicitUseCases(): void
    {
        $store = $this->createHandler()(new CreateStore(
            'CENTRE',
            'Centre',
            null,
            'Africa/Brazzaville',
            'XAF',
            'fr_CG',
            $this->context(),
        ));
        $loader = new TenantStoreLoader($this->stores);

        $update = new UpdateStoreHandler($loader, $this->stores, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard(), new RecordingSecurityAuditTrail());
        $store = $update(new UpdateStore($store->id(), 'Centre-ville', 'Plateau', 'Africa/Brazzaville', 'fr_CG', $this->context()));
        self::assertSame('Centre-ville', $store->name()->value());
        self::assertSame('Plateau', $store->address()?->value());

        $suspend = new SuspendStoreHandler($loader, $this->stores, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard(), new RecordingSecurityAuditTrail());
        $store = $suspend(new SuspendStore($store->id(), $this->context()));
        self::assertSame(StoreStatus::Suspended, $store->status());

        $reactivate = new ReactivateStoreHandler($loader, $this->stores, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard(), new RecordingSecurityAuditTrail());
        $store = $reactivate(new ReactivateStore($store->id(), $this->context()));
        self::assertSame(StoreStatus::Active, $store->status());
        self::assertSame(4, $store->version());
    }

    private function createHandler(): CreateStoreHandler
    {
        $uuid = (new SymfonyUuidFactory())->fromString(self::STORE_ID);

        return new CreateStoreHandler(
            $this->organizations,
            $this->stores,
            new class ($uuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            $this->clock,
            $this->transaction,
            new AllowAllAuthorizationService(),
            new AllowAllOperationalGuard(),
            new RecordingSecurityAuditTrail(),
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

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory());
    }
}

final class StoreUseCaseRepository implements StoreRepository
{
    /** @var array<string, Store> */
    private array $stores = [];

    public function save(Store $store): void
    {
        $this->stores[$store->id()->toString()] = $store;
    }
    public function get(OrganizationId $organizationId, StoreId $storeId): Store
    {
        return $this->find($organizationId, $storeId) ?? throw StoreNotFound::withId($storeId);
    }
    public function find(OrganizationId $organizationId, StoreId $storeId): ?Store
    {
        $store = $this->stores[$storeId->toString()] ?? null;
        return $store instanceof Store && $store->organizationId()->equals($organizationId) ? $store : null;
    }
    public function codeExists(OrganizationId $organizationId, StoreCode $code): bool
    {
        foreach ($this->stores as $store) {
            if ($store->organizationId()->equals($organizationId) && $store->code()->value() === $code->value()) {
                return true;
            }
        }
        return false;
    }
}

final class StoreUseCaseOrganizationRepository implements OrganizationRepository
{
    /** @var array<string, Organization> */
    private array $organizations = [];
    public function save(Organization $organization): void
    {
        $this->organizations[$organization->id()->toString()] = $organization;
    }
    public function get(OrganizationId $id): Organization
    {
        return $this->find($id) ?? throw OrganizationNotFound::withId($id);
    }
    public function find(OrganizationId $id): ?Organization
    {
        return $this->organizations[$id->toString()] ?? null;
    }
}
