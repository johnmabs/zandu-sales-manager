<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\FreshMembershipAccessGuard;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final class FreshMembershipAccessGuardTest extends TestCase
{
    public function testFreshActiveVersionIsAcceptedAndStaleVersionIsRejected(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);
        $userId = UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $actorId = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            $userId,
            [RoleAssignment::assign(
                (new SystemRoleCatalog($factory))->roles()[2]->id(),
                AccessScope::organization($organizationId),
                $actorId,
                new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
            )],
            $actorId,
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );
        $repository = new GuardMembershipRepository($membership);
        $guard = new FreshMembershipAccessGuard($repository, new GuardTenantTransaction());
        $context = fn(int $version): ActorContext => new ActorContext(
            $actorId,
            $organizationId,
            ActorType::User,
            CorrelationId::fromString('0198c729-19da-75be-b508-1a4b36cf8d7a', $factory),
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
            $userId,
            null,
            'member@example.com',
            $version,
        );

        $guard->assertFreshActiveMembership($context(1));
        self::assertTrue(true);
        $membership->suspend($actorId, new DateTimeImmutable('2026-08-22T11:00:00+00:00'));

        $this->expectException(LogicException::class);
        $guard->assertFreshActiveMembership($context(1));
    }
}

final readonly class GuardMembershipRepository implements OrganizationMembershipRepository
{
    public function __construct(private OrganizationMembership $membership) {}
    public function save(OrganizationMembership $membership): void {}
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        return $this->membership;
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership
    {
        return $this->membership;
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
    {
        return 0;
    }
}

final class GuardTenantTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
