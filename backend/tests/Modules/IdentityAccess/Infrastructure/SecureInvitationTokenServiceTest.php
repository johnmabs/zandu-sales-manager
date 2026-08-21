<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Infrastructure;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Infrastructure\Security\SecureInvitationTokenService;

final class SecureInvitationTokenServiceTest extends TestCase
{
    public function testItIssuesUrlSafeSinglePurposeTokensAndOnlyExposesTheirHashSeparately(): void
    {
        $service = new SecureInvitationTokenService('application-secret-pepper');
        $first = $service->issue();
        $second = $service->issue();

        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $first->reveal());
        self::assertSame(64, strlen($first->tokenHash()));
        self::assertSame($first->tokenHash(), $service->hash($first->reveal()));
        self::assertNotSame($first->reveal(), $first->tokenHash());
        self::assertNotSame($first->reveal(), $second->reveal());
    }

    public function testPepperSeparatesHashesBetweenApplications(): void
    {
        $token = 'public-token-value';

        self::assertNotSame(
            (new SecureInvitationTokenService('pepper-a'))->hash($token),
            (new SecureInvitationTokenService('pepper-b'))->hash($token),
        );
    }
}
