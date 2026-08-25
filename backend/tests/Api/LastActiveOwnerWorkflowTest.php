<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LastActiveOwnerWorkflowTest extends WebTestCase
{
    private const OWNER_EMAIL = 'last-owner@example.com';
    private const SECOND_OWNER_EMAIL = 'second-owner@example.com';
    private const PASSWORD = 'a-strong-password-for-zandu';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->clearRateLimiters();
        $this->cleanup();
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->cleanup();
        $this->clearRateLimiters();
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    public function testLastOwnerCannotBeDeactivatedButOneOfTwoOwnersCanBeRevoked(): void
    {
        $client = self::createClient();
        $owner = $this->registerOwnerAndLogin($client);

        $this->transition($client, $owner['token'], $owner['membershipId'], 'suspend');
        $this->assertDomainRuleViolation($client);
        $this->assertMembershipStatus($owner['membershipId'], 'ACTIVE');

        $this->transition($client, $owner['token'], $owner['membershipId'], 'revoke');
        $this->assertDomainRuleViolation($client);
        $this->assertMembershipStatus($owner['membershipId'], 'ACTIVE');

        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => self::SECOND_OWNER_EMAIL,
            'roleAssignments' => [[
                'roleCode' => 'ORGANIZATION_OWNER',
                'storeIds' => [],
            ]],
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $owner['token']]);
        self::assertResponseStatusCodeSame(201);
        $invitationToken = $this->payload($client)['token'] ?? null;
        self::assertIsString($invitationToken);

        $client->jsonRequest('POST', '/api/auth/invitations/' . $invitationToken . '/register', [
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);
        $secondOwner = $this->payload($client);
        $secondOwnerMembershipId = $this->entityManager->getConnection()->fetchOne(
            'SELECT id FROM identity_access.organization_memberships WHERE organization_id = ? AND user_id = ?',
            [$owner['organizationId'], $secondOwner['userId']],
        );
        self::assertIsString($secondOwnerMembershipId);

        $this->transition($client, $owner['token'], $secondOwnerMembershipId, 'revoke');
        self::assertResponseIsSuccessful();
        self::assertSame('REVOKED', $this->payload($client)['status']);
        $this->assertMembershipStatus($owner['membershipId'], 'ACTIVE');
        $this->assertMembershipStatus($secondOwnerMembershipId, 'REVOKED');
    }

    /** @return array{organizationId: string, membershipId: string, token: string} */
    private function registerOwnerAndLogin(KernelBrowser $client): array
    {
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => self::OWNER_EMAIL,
            'password' => self::PASSWORD,
            'organizationName' => 'Last owner tenant',
            'countryCode' => 'CG',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
        ]);
        self::assertResponseStatusCodeSame(201);
        $registration = $this->payload($client);
        $membershipId = $this->entityManager->getConnection()->fetchOne(
            'SELECT id FROM identity_access.organization_memberships WHERE organization_id = ? AND user_id = ?',
            [$registration['organizationId'], $registration['userId']],
        );
        self::assertIsString($membershipId);

        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => self::OWNER_EMAIL,
            'password' => self::PASSWORD,
        ]);
        self::assertResponseIsSuccessful();
        $token = $this->payload($client)['token'] ?? null;
        self::assertIsString($token);

        return [
            'organizationId' => $registration['organizationId'],
            'membershipId' => $membershipId,
            'token' => $token,
        ];
    }

    private function transition(KernelBrowser $client, string $token, string $membershipId, string $transition): void
    {
        $client->request('POST', sprintf('/api/members/%s/%s', $membershipId, $transition), server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
    }

    private function assertDomainRuleViolation(KernelBrowser $client): void
    {
        self::assertResponseStatusCodeSame(422);
        self::assertSame('DOMAIN_RULE_VIOLATION', $this->payload($client)['code'] ?? null);
    }

    private function assertMembershipStatus(string $membershipId, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->entityManager->getConnection()->fetchOne(
                'SELECT status FROM identity_access.organization_memberships WHERE id = ?',
                [$membershipId],
            ),
        );
    }

    /** @return array<string, mixed> */
    private function payload(KernelBrowser $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        $organizationId = $connection->fetchOne(
            'SELECT default_organization_id FROM identity_access.users WHERE email = ?',
            [self::OWNER_EMAIL],
        );
        if (is_string($organizationId)) {
            foreach ([self::OWNER_EMAIL, self::SECOND_OWNER_EMAIL] as $email) {
                $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [$email]);
            }
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [$organizationId]);
        }
        foreach ([self::OWNER_EMAIL, self::SECOND_OWNER_EMAIL] as $email) {
            $connection->executeStatement('DELETE FROM identity_access.users WHERE email = ?', [$email]);
        }
        $this->entityManager->clear();
    }

    private function clearRateLimiters(): void
    {
        $cache = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $cache);
        $cache->clear();
    }
}
