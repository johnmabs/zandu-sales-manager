<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ImmediateMembershipRevocationWorkflowTest extends WebTestCase
{
    private const OWNER_EMAIL = 'revocation-owner@example.com';
    private const MANAGER_EMAIL = 'revocation-manager@example.com';
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

    public function testSuspensionAndRevocationImmediatelyInvalidateExistingAccessTokens(): void
    {
        $client = self::createClient();
        $owner = $this->registerOwnerAndLogin($client);
        $store = $this->createStore($client, $owner['token']);
        $managerMembershipId = $this->inviteAndRegisterManager($client, $owner, $store['id']);
        $initialVersion = $this->authorizationVersion($managerMembershipId);
        $managerToken = $this->login($client, self::MANAGER_EMAIL);

        $this->updateStore($client, $managerToken, $store['id'], 'Before suspension');
        self::assertResponseIsSuccessful();

        $this->transitionMembership($client, $owner['token'], $managerMembershipId, 'suspend');
        self::assertResponseIsSuccessful();
        $suspended = $this->payload($client);
        self::assertSame('SUSPENDED', $suspended['status']);
        self::assertSame($initialVersion + 1, $suspended['authorizationVersion']);

        $this->updateStore($client, $managerToken, $store['id'], 'Forbidden while suspended');
        $this->assertUnauthenticated($client);
        $this->assertStoreName($store['id'], 'Before suspension');

        $this->transitionMembership($client, $owner['token'], $managerMembershipId, 'reactivate');
        self::assertResponseIsSuccessful();
        $reactivated = $this->payload($client);
        self::assertSame('ACTIVE', $reactivated['status']);
        self::assertSame($initialVersion + 2, $reactivated['authorizationVersion']);
        $freshManagerToken = $this->login($client, self::MANAGER_EMAIL);

        $this->updateStore($client, $freshManagerToken, $store['id'], 'Before revocation');
        self::assertResponseIsSuccessful();

        $this->transitionMembership($client, $owner['token'], $managerMembershipId, 'revoke');
        self::assertResponseIsSuccessful();
        $revoked = $this->payload($client);
        self::assertSame('REVOKED', $revoked['status']);
        self::assertSame($initialVersion + 3, $revoked['authorizationVersion']);

        $this->updateStore($client, $freshManagerToken, $store['id'], 'Forbidden after revocation');
        $this->assertUnauthenticated($client);
        $this->assertStoreName($store['id'], 'Before revocation');
    }

    /** @return array{organizationId: string, token: string} */
    private function registerOwnerAndLogin(KernelBrowser $client): array
    {
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => self::OWNER_EMAIL,
            'password' => self::PASSWORD,
            'organizationName' => 'Immediate revocation tenant',
            'countryCode' => 'CG',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
        ]);
        self::assertResponseStatusCodeSame(201);
        $registration = $this->payload($client);

        return [
            'organizationId' => $registration['organizationId'],
            'token' => $this->login($client, self::OWNER_EMAIL),
        ];
    }

    /** @return array<string, mixed> */
    private function createStore(KernelBrowser $client, string $token): array
    {
        $client->jsonRequest('POST', '/api/stores', [
            'code' => 'REVOKE',
            'name' => 'Before authorization changes',
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'currency' => 'XAF',
            'locale' => 'fr_CG',
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    /** @param array{organizationId: string, token: string} $owner */
    private function inviteAndRegisterManager(KernelBrowser $client, array $owner, string $storeId): string
    {
        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => self::MANAGER_EMAIL,
            'roleAssignments' => [[
                'roleCode' => 'STORE_MANAGER',
                'storeIds' => [$storeId],
            ]],
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $owner['token']]);
        self::assertResponseStatusCodeSame(201);
        $invitationToken = $this->payload($client)['token'] ?? null;
        self::assertIsString($invitationToken);

        $client->jsonRequest('POST', '/api/auth/invitations/' . $invitationToken . '/register', [
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);
        $userId = $this->payload($client)['userId'] ?? null;
        self::assertIsString($userId);
        $membershipId = $this->entityManager->getConnection()->fetchOne(
            'SELECT id FROM identity_access.organization_memberships WHERE organization_id = ? AND user_id = ?',
            [$owner['organizationId'], $userId],
        );
        self::assertIsString($membershipId);

        return $membershipId;
    }

    private function login(KernelBrowser $client, string $email): string
    {
        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $token = $this->payload($client)['token'] ?? null;
        self::assertIsString($token);

        return $token;
    }

    private function updateStore(KernelBrowser $client, string $token, string $storeId, string $name): void
    {
        $client->jsonRequest('PATCH', '/api/stores/' . $storeId, [
            'name' => $name,
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'locale' => 'fr_CG',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ]);
    }

    private function transitionMembership(KernelBrowser $client, string $token, string $membershipId, string $transition): void
    {
        $client->request('POST', sprintf('/api/members/%s/%s', $membershipId, $transition), server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
    }

    private function assertUnauthenticated(KernelBrowser $client): void
    {
        self::assertResponseStatusCodeSame(401);
        self::assertSame('UNAUTHENTICATED', $this->payload($client)['code'] ?? null);
    }

    private function assertStoreName(string $storeId, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->entityManager->getConnection()->fetchOne('SELECT name FROM organization.stores WHERE id = ?', [$storeId]),
        );
    }

    private function authorizationVersion(string $membershipId): int
    {
        return (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT authorization_version FROM identity_access.organization_memberships WHERE id = ?',
            [$membershipId],
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
            foreach ([self::OWNER_EMAIL, self::MANAGER_EMAIL] as $email) {
                $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [$email]);
            }
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [$organizationId]);
        }
        foreach ([self::OWNER_EMAIL, self::MANAGER_EMAIL] as $email) {
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
