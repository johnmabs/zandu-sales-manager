<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\InvitationViewFactory;
use Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember\CreatedOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;

final class InvitationViewFactoryTest extends TestCase
{
    public function testItExposesTheRawTokenOnceWithoutLeakingItsHash(): void
    {
        $uuids = new SymfonyUuidFactory();
        $invitation = OrganizationInvitation::invite(
            OrganizationInvitationId::fromString('0198c728-a648-75b7-b7d7-c69d0bf84390', $uuids),
            OrganizationId::fromString('0198c729-19da-75be-b508-1a4b36cf8d7a', $uuids),
            InvitationEmail::fromString('member@example.com'),
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $uuids),
            'never-expose-this-hash',
            new DateTimeImmutable('2026-08-30T08:00:00+00:00'),
            [IntendedRoleAssignment::forRole(RoleCode::CASHIER)],
            new DateTimeImmutable('2026-08-22T08:00:00+00:00'),
        );

        $view = (new InvitationViewFactory())->fromCreated(new CreatedOrganizationInvitation($invitation, 'raw-token'));
        $serialized = json_encode($view, JSON_THROW_ON_ERROR);

        self::assertSame('raw-token', $view->token);
        self::assertSame('member@example.com', $view->invitation->email);
        self::assertSame('PENDING', $view->invitation->status);
        self::assertSame([['roleCode' => RoleCode::CASHIER, 'storeIds' => []]], $view->invitation->roleAssignments);
        self::assertStringNotContainsString('never-expose-this-hash', $serialized);
        self::assertStringNotContainsString('tokenHash', $serialized);
    }
}
