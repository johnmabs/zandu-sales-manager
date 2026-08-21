<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Infrastructure;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Infrastructure\Security\SecureInvitationTokenService;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\OrganizationId;

final class SecureInvitationTokenServiceTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';

    public function testItIssuesUrlSafeSinglePurposeTokensAndOnlyExposesTheirHashSeparately(): void
    {
        $service = new SecureInvitationTokenService('application-secret-pepper', new SymfonyUuidFactory());
        $organizationId = OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory());
        $first = $service->issue($organizationId);
        $second = $service->issue($organizationId);

        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}\.[A-Za-z0-9_-]{43}$/', $first->reveal());
        self::assertTrue($organizationId->equals($service->organizationId($first->reveal())));
        self::assertSame(64, strlen($first->tokenHash()));
        self::assertSame($first->tokenHash(), $service->hash($first->reveal()));
        self::assertNotSame($first->reveal(), $first->tokenHash());
        self::assertNotSame($first->reveal(), $second->reveal());
    }

    public function testPepperSeparatesHashesBetweenApplications(): void
    {
        $token = 'public-token-value';

        self::assertNotSame(
            (new SecureInvitationTokenService('pepper-a', new SymfonyUuidFactory()))->hash($token),
            (new SecureInvitationTokenService('pepper-b', new SymfonyUuidFactory()))->hash($token),
        );
    }
}
