<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StoreScopedAuthorizationWorkflowTest extends WebTestCase
{
    private const OWNER_EMAIL = 'scope-owner@example.com';
    private const MANAGER_EMAIL = 'scope-manager@example.com';
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

    public function testStoreManagerCanUpdateOnlyTheStoreInItsScope(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerOwnerAndLogin($client);
        $storeA = $this->createStore($client, $ownerToken, 'STORE-A', 'Store A');
        $storeB = $this->createStore($client, $ownerToken, 'STORE-B', 'Store B');

        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => self::MANAGER_EMAIL,
            'roleAssignments' => [[
                'roleCode' => 'STORE_MANAGER',
                'storeIds' => [$storeA['id']],
            ]],
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $ownerToken]);
        self::assertResponseStatusCodeSame(201);
        $invitationToken = $this->payload($client)['token'] ?? null;
        self::assertIsString($invitationToken);

        $client->jsonRequest('POST', '/api/auth/invitations/' . $invitationToken . '/register', [
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);
        $managerToken = $this->login($client, self::MANAGER_EMAIL);

        $this->updateStore($client, $managerToken, $storeA['id'], 'Store A managed');
        self::assertResponseIsSuccessful();
        self::assertSame('Store A managed', $this->payload($client)['name']);

        $this->updateStore($client, $managerToken, $storeB['id'], 'Store B forbidden');
        self::assertResponseStatusCodeSame(403);
        self::assertSame('FORBIDDEN', $this->payload($client)['code'] ?? null);
        self::assertSame(
            'Store B',
            $this->entityManager->getConnection()->fetchOne('SELECT name FROM organization.stores WHERE id = ?', [$storeB['id']]),
        );
    }

    private function registerOwnerAndLogin(KernelBrowser $client): string
    {
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => self::OWNER_EMAIL,
            'password' => self::PASSWORD,
            'organizationName' => 'Store scope tenant',
            'countryCode' => 'CG',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->login($client, self::OWNER_EMAIL);
    }

    private function login(KernelBrowser $client, string $email): string
    {
        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $token = $this->payload($client)['token'] ?? null;
        self::assertIsString($token);

        return $token;
    }

    /** @return array<string, mixed> */
    private function createStore(KernelBrowser $client, string $token, string $code, string $name): array
    {
        $client->jsonRequest('POST', '/api/stores', [
            'code' => $code,
            'name' => $name,
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'currency' => 'XAF',
            'locale' => 'fr_CG',
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
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
