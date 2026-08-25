<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Application;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\ActivateUnitOfMeasure\ActivateUnitOfMeasure;
use Zandu\Modules\Catalog\Application\ActivateUnitOfMeasure\ActivateUnitOfMeasureHandler;
use Zandu\Modules\Catalog\Application\CreateUnitOfMeasure\CreateUnitOfMeasure;
use Zandu\Modules\Catalog\Application\CreateUnitOfMeasure\CreateUnitOfMeasureHandler;
use Zandu\Modules\Catalog\Application\DeactivateUnitOfMeasure\DeactivateUnitOfMeasure;
use Zandu\Modules\Catalog\Application\DeactivateUnitOfMeasure\DeactivateUnitOfMeasureHandler;
use Zandu\Modules\Catalog\Application\TenantUnitOfMeasureLoader;
use Zandu\Modules\Catalog\Application\UpdateUnitOfMeasure\UpdateUnitOfMeasure;
use Zandu\Modules\Catalog\Application\UpdateUnitOfMeasure\UpdateUnitOfMeasureHandler;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCode;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCodeAlreadyExists;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureNotFound;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureStatus;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class UnitOfMeasureHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const OTHER_ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906503';
    private const UNIT_ID = '0198d273-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    private UnitOfMeasureUseCaseRepository $units;
    private RecordingTenantTransaction $transaction;
    private FrozenClock $clock;
    private RecordingUnitOfMeasureAuthorization $authorization;
    private RecordingUnitOfMeasureOperationalGuard $operationalGuard;

    protected function setUp(): void
    {
        $this->units = new UnitOfMeasureUseCaseRepository();
        $this->transaction = new RecordingTenantTransaction();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-25T17:00:00Z'));
        $this->authorization = new RecordingUnitOfMeasureAuthorization();
        $this->operationalGuard = new RecordingUnitOfMeasureOperationalGuard();
    }

    public function testItCreatesANormalizedTenantOwnedUnit(): void
    {
        $unit = $this->createHandler()(new CreateUnitOfMeasure(
            'kg',
            ' Kilogramme ',
            'mass',
            3,
            'half_up',
            $this->context(),
        ));

        self::assertSame(self::UNIT_ID, $unit->id()->toString());
        self::assertSame(self::ORGANIZATION_ID, $unit->organizationId()->toString());
        self::assertSame('KG', $unit->code()->value());
        self::assertSame(RoundingMode::HalfUp, $unit->roundingMode());
        self::assertSame(UnitOfMeasureStatus::Active, $unit->status());
        self::assertSame(self::ORGANIZATION_ID, $this->transaction->lastOrganizationId?->toString());
        self::assertSame([PermissionCode::UnitOfMeasureCreate], $this->authorization->permissions);
        self::assertSame(1, $this->operationalGuard->tenantChecks);
    }

    public function testItRejectsADuplicateCodeWithinTheTenant(): void
    {
        $handler = $this->createHandler();
        $command = new CreateUnitOfMeasure('EA', 'Article', 'COUNT', 0, 'UNNECESSARY', $this->context());
        $handler($command);

        $this->expectException(UnitOfMeasureCodeAlreadyExists::class);
        $handler($command);
    }

    public function testItRejectsAnUnknownRoundingMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createHandler()(new CreateUnitOfMeasure(
            'KG',
            'Kilogramme',
            'MASS',
            3,
            'SILENT',
            $this->context(),
        ));
    }

    public function testUpdateDeactivateAndActivateAreDedicatedUseCases(): void
    {
        $unit = $this->createHandler()(new CreateUnitOfMeasure(
            'KG',
            'Kilogramme',
            'MASS',
            3,
            'HALF_UP',
            $this->context(),
        ));
        $loader = new TenantUnitOfMeasureLoader($this->units);

        $update = new UpdateUnitOfMeasureHandler($loader, $this->units, $this->clock, $this->transaction, $this->authorization, $this->operationalGuard);
        $unit = $update(new UpdateUnitOfMeasure(
            $unit->id(),
            'Kilogramme net',
            'MASS',
            4,
            'HALF_EVEN',
            $this->context(),
        ));
        self::assertSame('Kilogramme net', $unit->name()->value());
        self::assertSame(4, $unit->precision()->value());
        self::assertSame(RoundingMode::HalfEven, $unit->roundingMode());

        $deactivate = new DeactivateUnitOfMeasureHandler($loader, $this->units, $this->clock, $this->transaction, $this->authorization, $this->operationalGuard);
        $unit = $deactivate(new DeactivateUnitOfMeasure($unit->id(), $this->context()));
        self::assertSame(UnitOfMeasureStatus::Inactive, $unit->status());

        $activate = new ActivateUnitOfMeasureHandler($loader, $this->units, $this->clock, $this->transaction, $this->authorization, $this->operationalGuard);
        $unit = $activate(new ActivateUnitOfMeasure($unit->id(), $this->context()));
        self::assertSame(UnitOfMeasureStatus::Active, $unit->status());
        self::assertSame(4, $unit->version());
        self::assertSame([
            PermissionCode::UnitOfMeasureCreate,
            PermissionCode::UnitOfMeasureUpdate,
            PermissionCode::UnitOfMeasureDeactivate,
            PermissionCode::UnitOfMeasureActivate,
        ], $this->authorization->permissions);
        self::assertSame(4, $this->operationalGuard->tenantChecks);
    }

    public function testLoaderCannotCrossTheActorTenantBoundary(): void
    {
        $unit = $this->createHandler()(new CreateUnitOfMeasure(
            'KG',
            'Kilogramme',
            'MASS',
            3,
            'HALF_UP',
            $this->context(),
        ));
        $handler = new DeactivateUnitOfMeasureHandler(
            new TenantUnitOfMeasureLoader($this->units),
            $this->units,
            $this->clock,
            $this->transaction,
            $this->authorization,
            $this->operationalGuard,
        );

        $this->expectException(UnitOfMeasureNotFound::class);
        $handler(new DeactivateUnitOfMeasure($unit->id(), $this->context(self::OTHER_ORGANIZATION_ID)));
    }

    private function createHandler(): CreateUnitOfMeasureHandler
    {
        $uuid = (new SymfonyUuidFactory())->fromString(self::UNIT_ID);

        return new CreateUnitOfMeasureHandler(
            $this->units,
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
            $this->operationalGuard,
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
            new DateTimeImmutable('2026-08-25T16:00:00Z'),
        );
    }
}

final class RecordingUnitOfMeasureAuthorization implements AuthorizationService
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

final class RecordingUnitOfMeasureOperationalGuard implements OperationalGuard
{
    public int $tenantChecks = 0;

    public function assertTenant(ActorContext $actorContext, OperationalMode $mode = OperationalMode::Standard): void
    {
        ++$this->tenantChecks;
        TestCase::assertSame(OperationalMode::Standard, $mode);
    }

    public function assertStore(ActorContext $actorContext, StoreId $storeId, OperationalMode $mode = OperationalMode::Standard): void
    {
        TestCase::fail('UnitOfMeasure mutations must not use a store-scoped operational guard.');
    }
}

final class UnitOfMeasureUseCaseRepository implements UnitOfMeasureRepository
{
    /** @var array<string, UnitOfMeasure> */
    private array $units = [];

    public function save(UnitOfMeasure $unit): void
    {
        $this->units[$unit->id()->toString()] = $unit;
    }

    public function get(OrganizationId $organizationId, UnitOfMeasureId $unitId): UnitOfMeasure
    {
        return $this->find($organizationId, $unitId) ?? throw UnitOfMeasureNotFound::withId($unitId);
    }

    public function find(OrganizationId $organizationId, UnitOfMeasureId $unitId): ?UnitOfMeasure
    {
        $unit = $this->units[$unitId->toString()] ?? null;

        return $unit instanceof UnitOfMeasure && $unit->organizationId()->equals($organizationId) ? $unit : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return array_values(array_filter(
            $this->units,
            static fn(UnitOfMeasure $unit): bool => $unit->organizationId()->equals($organizationId),
        ));
    }

    public function codeExists(OrganizationId $organizationId, UnitOfMeasureCode $code): bool
    {
        foreach ($this->units as $unit) {
            if ($unit->organizationId()->equals($organizationId) && $unit->code()->value() === $code->value()) {
                return true;
            }
        }

        return false;
    }
}

final class RecordingTenantTransaction implements TenantTransaction
{
    public ?OrganizationId $lastOrganizationId = null;

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        $this->lastOrganizationId = $organizationId;

        return $operation();
    }
}
