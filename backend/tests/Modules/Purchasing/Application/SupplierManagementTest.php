<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Purchasing\Application\ActivateSupplier\ActivateSupplier;
use Zandu\Modules\Purchasing\Application\ActivateSupplier\ActivateSupplierHandler;
use Zandu\Modules\Purchasing\Application\ArchiveSupplier\ArchiveSupplier;
use Zandu\Modules\Purchasing\Application\ArchiveSupplier\ArchiveSupplierHandler;
use Zandu\Modules\Purchasing\Application\CreateSupplier\CreateSupplier;
use Zandu\Modules\Purchasing\Application\CreateSupplier\CreateSupplierHandler;
use Zandu\Modules\Purchasing\Application\DeactivateSupplier\DeactivateSupplier;
use Zandu\Modules\Purchasing\Application\DeactivateSupplier\DeactivateSupplierHandler;
use Zandu\Modules\Purchasing\Application\TenantSupplierLoader;
use Zandu\Modules\Purchasing\Application\UpdateSupplier\UpdateSupplier;
use Zandu\Modules\Purchasing\Application\UpdateSupplier\UpdateSupplierHandler;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierNotFound;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierStatus;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class SupplierManagementTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d921-ab09-73bf-b631-c307fd6ed08d';
    private const OTHER_ORGANIZATION_ID = '0198d922-70a2-71df-8beb-b7ae882c8dba';
    private const SUPPLIER_ID = '0198d923-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    private SupplierUseCaseRepository $suppliers;
    private RecordingSupplierAuthorization $authorization;
    private RecordingSupplierOperationalGuard $guard;
    private RecordingSupplierTransaction $transaction;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->suppliers = new SupplierUseCaseRepository();
        $this->authorization = new RecordingSupplierAuthorization();
        $this->guard = new RecordingSupplierOperationalGuard();
        $this->transaction = new RecordingSupplierTransaction();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-28T20:00:00Z'));
    }

    public function testItCreatesANormalizedTenantOwnedSupplier(): void
    {
        $supplier = $this->createHandler()(new CreateSupplier(
            ' Acme   RDC ',
            null,
            ' INFO@ACME.EXAMPLE ',
            null,
            null,
            $this->context(),
        ));

        self::assertSame(self::SUPPLIER_ID, $supplier->id()->toString());
        self::assertSame(self::ORGANIZATION_ID, $supplier->organizationId()->toString());
        self::assertSame('Acme RDC', $supplier->name()->value());
        self::assertSame('info@acme.example', $supplier->email());
        self::assertSame([PermissionCode::SupplierCreate], $this->authorization->permissions);
        self::assertSame(1, $this->guard->tenantChecks);
        self::assertSame(self::ORGANIZATION_ID, $this->transaction->organizationId?->toString());
    }

    public function testDedicatedUseCasesUpdateAndControlTheLifecycle(): void
    {
        $supplier = $this->createHandler()(new CreateSupplier('Acme', null, null, null, null, $this->context()));
        $loader = new TenantSupplierLoader($this->suppliers);

        $supplier = (new UpdateSupplierHandler($loader, $this->suppliers, $this->clock, $this->transaction, $this->authorization, $this->guard))(
            new UpdateSupplier($supplier->id(), 'Acme Distribution', '+242 06 000 0000', null, 'Brazzaville', null, \Zandu\SharedKernel\Versioning\ExpectedVersion::fromInt($supplier->version()), $this->context()),
        );
        $supplier = (new DeactivateSupplierHandler($loader, $this->suppliers, $this->clock, $this->transaction, $this->authorization, $this->guard))(
            new DeactivateSupplier($supplier->id(), $this->context()),
        );
        $supplier = (new ActivateSupplierHandler($loader, $this->suppliers, $this->clock, $this->transaction, $this->authorization, $this->guard))(
            new ActivateSupplier($supplier->id(), $this->context()),
        );
        $supplier = (new ArchiveSupplierHandler($loader, $this->suppliers, $this->clock, $this->transaction, $this->authorization, $this->guard))(
            new ArchiveSupplier($supplier->id(), $this->context()),
        );

        self::assertSame('Acme Distribution', $supplier->name()->value());
        self::assertSame(SupplierStatus::Archived, $supplier->status());
        self::assertSame(5, $supplier->version());
        self::assertSame([
            PermissionCode::SupplierCreate,
            PermissionCode::SupplierUpdate,
            PermissionCode::SupplierUpdate,
            PermissionCode::SupplierUpdate,
            PermissionCode::SupplierArchive,
        ], $this->authorization->permissions);
        self::assertSame(5, $this->guard->tenantChecks);
    }

    public function testTenantLoaderCannotCrossTheActorBoundary(): void
    {
        $supplier = $this->createHandler()(new CreateSupplier('Acme', null, null, null, null, $this->context()));
        $handler = new DeactivateSupplierHandler(
            new TenantSupplierLoader($this->suppliers),
            $this->suppliers,
            $this->clock,
            $this->transaction,
            $this->authorization,
            $this->guard,
        );

        $this->expectException(SupplierNotFound::class);
        $handler(new DeactivateSupplier($supplier->id(), $this->context(self::OTHER_ORGANIZATION_ID)));
    }

    private function createHandler(): CreateSupplierHandler
    {
        $uuid = (new SymfonyUuidFactory())->fromString(self::SUPPLIER_ID);

        return new CreateSupplierHandler(
            $this->suppliers,
            new class ($uuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            $this->clock,
            $this->transaction,
            $this->authorization,
            $this->guard,
        );
    }

    private function context(string $organizationId = self::ORGANIZATION_ID): ActorContext
    {
        $factory = new SymfonyUuidFactory();

        return new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            OrganizationId::fromString($organizationId, $factory),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            new DateTimeImmutable('2026-08-28T20:00:00Z'),
        );
    }
}

final class SupplierUseCaseRepository implements SupplierRepository
{
    /** @var array<string, Supplier> */
    private array $suppliers = [];

    public function save(Supplier $supplier): void
    {
        $this->suppliers[$supplier->id()->toString()] = $supplier;
    }

    public function get(OrganizationId $organizationId, SupplierId $supplierId): Supplier
    {
        return $this->find($organizationId, $supplierId) ?? throw SupplierNotFound::withId($supplierId);
    }

    public function find(OrganizationId $organizationId, SupplierId $supplierId): ?Supplier
    {
        $supplier = $this->suppliers[$supplierId->toString()] ?? null;

        return $supplier instanceof Supplier && $supplier->organizationId()->equals($organizationId) ? $supplier : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return array_values(array_filter(
            $this->suppliers,
            static fn(Supplier $supplier): bool => $supplier->organizationId()->equals($organizationId),
        ));
    }
}

final class RecordingSupplierAuthorization implements AuthorizationService
{
    /** @var list<PermissionCode> */
    public array $permissions = [];

    public function authorize(ActorContext $actorContext, PermissionCode $permission, ResourceScope $resourceScope): void
    {
        $this->permissions[] = $permission;
        TestCase::assertTrue($actorContext->organizationId()->equals($resourceScope->organizationId));
        TestCase::assertNull($resourceScope->storeId);
    }
}

final class RecordingSupplierOperationalGuard implements OperationalGuard
{
    public int $tenantChecks = 0;

    public function assertTenant(ActorContext $actorContext, OperationalMode $mode = OperationalMode::Standard): void
    {
        ++$this->tenantChecks;
        TestCase::assertSame(OperationalMode::Standard, $mode);
    }

    public function assertStore(ActorContext $actorContext, StoreId $storeId, OperationalMode $mode = OperationalMode::Standard): void
    {
        TestCase::fail('Supplier mutations must not use a store-scoped operational guard.');
    }
}

final class RecordingSupplierTransaction implements TenantTransaction
{
    public ?OrganizationId $organizationId = null;

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        $this->organizationId = $organizationId;

        return $operation();
    }
}
