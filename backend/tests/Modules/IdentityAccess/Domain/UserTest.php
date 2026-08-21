<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\User\User;
use Zandu\Modules\IdentityAccess\Domain\User\UserEmail;
use Zandu\Modules\IdentityAccess\Domain\User\UserStatus;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UserId;

final class UserTest extends TestCase
{
    public function testItRegistersAnActiveUserWithCanonicalEmail(): void
    {
        $factory = new SymfonyUuidFactory();
        $user = User::register(
            UserId::fromString('0198d601-147c-72d5-b75a-a936797ff9c7', $factory),
            ActorId::fromString('0198d601-147c-72d5-b75a-a936797ff9c9', $factory),
            UserEmail::fromString(' User@Example.COM '),
            OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory),
            '$2y$13$already.hashed.password.value',
            new DateTimeImmutable('2026-08-23T01:00:00+00:00'),
        );

        self::assertSame('user@example.com', $user->email()->value());
        self::assertSame(UserStatus::Active, $user->status());
        self::assertSame(1, $user->version());
    }
}
