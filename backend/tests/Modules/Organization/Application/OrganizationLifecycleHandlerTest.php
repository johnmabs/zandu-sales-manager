<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Application\ReactivateOrganization\ReactivateOrganization;
use Zandu\Modules\Organization\Application\ReactivateOrganization\ReactivateOrganizationHandler;
use Zandu\Modules\Organization\Application\RequestOrganizationClosure\RequestOrganizationClosure;
use Zandu\Modules\Organization\Application\RequestOrganizationClosure\RequestOrganizationClosureHandler;
use Zandu\Modules\Organization\Application\SuspendOrganization\SuspendOrganization;
use Zandu\Modules\Organization\Application\SuspendOrganization\SuspendOrganizationHandler;
use Zandu\Modules\Organization\Application\TenantOrganizationLoader;
use Zandu\Modules\Organization\Application\UpdateOrganization\UpdateOrganization;
use Zandu\Modules\Organization\Application\UpdateOrganization\UpdateOrganizationHandler;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationNotFound;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\OrganizationStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class OrganizationLifecycleHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const OTHER_ORGANIZATION_ID = '0198d1b3-2438-75a0-bf19-eab21453492d';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    private LifecycleOrganizationRepository $repository;
    private TenantOrganizationLoader $loader;
    private FrozenClock $clock;
    private InMemoryTenantTransaction $transaction;

    protected function setUp(): void
    {
        $this->repository = new LifecycleOrganizationRepository();
        $this->repository->save($this->organization());
        $this->loader = new TenantOrganizationLoader($this->repository);
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-22T10:00:00+00:00'));
        $this->transaction = new InMemoryTenantTransaction();
    }

    public function testProfileUpdateUsesTheTrustedActorContext(): void
    {
        $handler = new UpdateOrganizationHandler($this->loader, $this->repository, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard());
        $organization = $handler(new UpdateOrganization(
            $this->organizationId(),
            'Zandu Congo',
            'CG',
            'XAF',
            'Africa/Brazzaville',
            'fr_CG',
            $this->actorContext(),
        ));

        self::assertSame('Zandu Congo', $organization->name()->value());
        self::assertSame(self::ACTOR_ID, $organization->updatedBy()->toString());
        self::assertSame(2, $organization->version());
        self::assertSame(self::ORGANIZATION_ID, $this->transaction->lastOrganizationId?->toString());
    }

    public function testSuspensionAndReactivationAreExplicitUseCases(): void
    {
        $suspend = new SuspendOrganizationHandler($this->loader, $this->repository, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard());
        $reactivate = new ReactivateOrganizationHandler($this->loader, $this->repository, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard());

        $organization = $suspend(new SuspendOrganization($this->organizationId(), $this->actorContext()));
        self::assertSame(OrganizationStatus::Suspended, $organization->status());

        $organization = $reactivate(new ReactivateOrganization($this->organizationId(), $this->actorContext()));
        self::assertSame(OrganizationStatus::Active, $organization->status());
        self::assertSame(3, $organization->version());
    }

    public function testClosureRequestIsAnExplicitUseCase(): void
    {
        $handler = new RequestOrganizationClosureHandler($this->loader, $this->repository, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard());

        $organization = $handler(new RequestOrganizationClosure($this->organizationId(), $this->actorContext()));

        self::assertSame(OrganizationStatus::ClosurePending, $organization->status());
        self::assertSame(self::ACTOR_ID, $organization->closureRequestedBy()?->toString());
    }

    public function testCrossTenantLookupIsReportedAsNotFound(): void
    {
        $factory = new SymfonyUuidFactory();
        $handler = new SuspendOrganizationHandler($this->loader, $this->repository, $this->clock, $this->transaction, new AllowAllAuthorizationService(), new AllowAllOperationalGuard());

        $this->expectException(OrganizationNotFound::class);
        $handler(new SuspendOrganization(
            OrganizationId::fromString(self::OTHER_ORGANIZATION_ID, $factory),
            $this->actorContext(),
        ));
    }

    private function organization(): Organization
    {
        return Organization::create(
            $this->organizationId(),
            OrganizationName::fromString('Zandu'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T08:00:00+00:00'),
        );
    }

    private function actorContext(): ActorContext
    {
        $factory = new SymfonyUuidFactory();

        return new ActorContext(
            $this->actorId(),
            $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
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

final class LifecycleOrganizationRepository implements OrganizationRepository
{
    /** @var array<string,Organization> */
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
