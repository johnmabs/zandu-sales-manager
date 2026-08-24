<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\ProvisionInitialOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class ProvisionInitialOrganizationOwnerTest extends TestCase
{
    public function testItCreatesTheInitialActiveOwnerMembership(): void
    {
        $factory = new SymfonyUuidFactory();
        $repository = new InitialOwnerMembershipRepository();
        $catalog = new SystemRoleCatalog($factory);
        $membershipUuid = $factory->fromString('0198d601-147c-72d5-b75a-a936797ff9c8');
        $provisioner = new ProvisionInitialOrganizationOwner(
            $repository,
            $catalog,
            new class ($membershipUuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
        );
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);

        $provisioner->provision($organizationId, $this->context($factory, $organizationId), new DateTimeImmutable('2026-08-21T10:00:00+00:00'));

        self::assertSame(MembershipStatus::Active, $repository->membership?->status());
        self::assertTrue($repository->membership?->hasRoleId($catalog->organizationOwnerRoleId()));
        self::assertSame(1, $repository->membership?->authorizationVersion());
    }

    private function context(SymfonyUuidFactory $factory, OrganizationId $organizationId): ActorContext
    {
        return new ActorContext(
            ActorId::fromString('0198d601-147c-72d5-b75a-a936797ff9c9', $factory),
            $organizationId,
            ActorType::User,
            CorrelationId::fromString('0198d601-147c-72d5-b75a-a936797ff9c6', $factory),
            new DateTimeImmutable('2026-08-21T09:00:00+00:00'),
            UserId::fromString('0198d601-147c-72d5-b75a-a936797ff9c7', $factory),
        );
    }
}

final class InitialOwnerMembershipRepository implements OrganizationMembershipRepository
{
    public ?OrganizationMembership $membership = null;
    public function save(OrganizationMembership $membership): void
    {
        $this->membership = $membership;
    }
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        return $this->membership ?? throw new LogicException('Membership not found.');
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership
    {
        return $this->membership;
    }
    public function findAll(OrganizationId $organizationId): array
    {
        return null !== $this->membership ? [$this->membership] : [];
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
    {
        return null === $this->membership ? 0 : 1;
    }
}
