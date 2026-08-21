<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Organization\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Organization\Application\Contract\InitialOrganizationOwnerProvisioner;
use Zandu\Modules\Organization\Application\CreateOrganization\CreateOrganization;
use Zandu\Modules\Organization\Application\CreateOrganization\CreateOrganizationHandler;
use Zandu\Modules\Organization\Domain\Event\OrganizationCreated;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationNotFound;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\OrganizationStatus;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CreateOrganizationHandlerTest extends TestCase
{
    private const NEW_ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CURRENT_ORGANIZATION_ID = '0198c728-a648-75b7-b7d7-c69d0bf84390';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    public function testItCreatesAndPersistsAnActiveOrganization(): void
    {
        $factory = new SymfonyUuidFactory();
        $repository = new InMemoryOrganizationRepository();
        $generatedUuid = $factory->fromString(self::NEW_ORGANIZATION_ID);
        $ownerProvisioner = new RecordingInitialOwnerProvisioner();
        $handler = new CreateOrganizationHandler(
            $repository,
            new class ($generatedUuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            new FrozenClock(new DateTimeImmutable('2026-08-22T08:00:00+00:00')),
            new InMemoryTenantTransaction(),
            $ownerProvisioner,
        );

        $organization = $handler(new CreateOrganization(
            'Zandu Congo',
            'CG',
            'XAF',
            'Africa/Brazzaville',
            'fr_CG',
            new ActorContext(
                ActorId::fromString(self::ACTOR_ID, $factory),
                OrganizationId::fromString(self::CURRENT_ORGANIZATION_ID, $factory),
                ActorType::User,
                CorrelationId::fromString(self::CORRELATION_ID, $factory),
                new DateTimeImmutable('2026-08-22T07:00:00+00:00'),
            ),
        ));

        self::assertSame(self::NEW_ORGANIZATION_ID, $organization->id()->toString());
        self::assertSame(OrganizationStatus::Active, $organization->status());
        self::assertSame($organization, $repository->get($organization->id()));
        self::assertInstanceOf(OrganizationCreated::class, $organization->releaseEvents()[0]);
        self::assertSame(self::NEW_ORGANIZATION_ID, $ownerProvisioner->organizationId?->toString());
    }
}

final class RecordingInitialOwnerProvisioner implements InitialOrganizationOwnerProvisioner
{
    public ?OrganizationId $organizationId = null;

    public function provision(OrganizationId $organizationId, ActorContext $actorContext, DateTimeImmutable $occurredAt): void
    {
        $this->organizationId = $organizationId;
    }
}

final class InMemoryOrganizationRepository implements OrganizationRepository
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
