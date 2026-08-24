<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MultiStoreAdministrationWorkflowTest extends WebTestCase
{
    private const OWNER_A_EMAIL = 'multi-store-a@example.com';
    private const OWNER_B_EMAIL = 'multi-store-b@example.com';
    private const PASSWORD = 'a-strong-password-for-zandu';
    private const STORE_MANAGER_ROLE_ID = '00000000-0000-7000-8000-000000000002';

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

    public function testMultiStoreAdministrationRespectsTenantCodesScopesAndLifecycle(): void
    {
        $client = self::createClient();
        $tenantA = $this->registerAndLogin($client, self::OWNER_A_EMAIL, 'Organization A');
        $storeA1 = $this->createStore($client, $tenantA['token'], 'CENTRE', 'Store A1');
        $storeA2 = $this->createStore($client, $tenantA['token'], 'NORD', 'Store A2');

        $client->jsonRequest('POST', '/api/stores', $this->storePayload('CENTRE', 'Duplicate A'), [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token'],
        ]);
        $this->assertError($client, 409, 'CONFLICT');

        $tenantB = $this->registerAndLogin($client, self::OWNER_B_EMAIL, 'Organization B');
        $storeB = $this->createStore($client, $tenantB['token'], 'CENTRE', 'Store B');
        self::assertNotSame($storeA1['id'], $storeB['id']);
        self::assertSame('CENTRE', $storeB['code']);

        $client->jsonRequest('POST', sprintf('/api/members/%s/role-assignments', $tenantA['membershipId']), [
            'roleId' => self::STORE_MANAGER_ROLE_ID,
            'scopeType' => 'SELECTED_STORES',
            'storeIds' => [$storeA1['id'], $storeA2['id']],
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token']]);
        self::assertResponseIsSuccessful();
        $membership = $this->payload($client);
        $assignment = $this->assignment($membership['roleAssignments'], self::STORE_MANAGER_ROLE_ID);
        self::assertSame('SELECTED_STORES', $assignment['scopeType']);
        self::assertEqualsCanonicalizing([$storeA1['id'], $storeA2['id']], $assignment['storeIds']);

        $tenantA['token'] = $this->login($client, self::OWNER_A_EMAIL);
        $client->request('POST', sprintf('/api/stores/%s/suspend', $storeA1['id']), server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('SUSPENDED', $this->payload($client)['status']);

        $client->request('POST', sprintf('/api/stores/%s/reactivate', $storeA1['id']), server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('ACTIVE', $this->payload($client)['status']);

        $client->jsonRequest('POST', sprintf('/api/stores/%s/closure-request', $storeA1['id']), [
            'reason' => 'End of lease',
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token']]);
        self::assertResponseIsSuccessful();
        $closure = $this->payload($client);
        self::assertSame($storeA1['id'], $closure['storeId']);
        self::assertSame('READY', $closure['status']);
        self::assertSame([], $closure['blockers']);

        $client->request('GET', '/api/stores', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token'],
            'HTTP_ACCEPT' => 'application/json',
        ]);
        self::assertResponseIsSuccessful();
        $stores = $this->payload($client);
        self::assertCount(2, $stores);
        self::assertEqualsCanonicalizing([$storeA1['id'], $storeA2['id']], array_column($stores, 'id'));
    }

    /** @return array{organizationId: string, membershipId: string, token: string} */
    private function registerAndLogin(KernelBrowser $client, string $email, string $organizationName): array
    {
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => $email,
            'password' => self::PASSWORD,
            'organizationName' => $organizationName,
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

        return [
            'organizationId' => $registration['organizationId'],
            'membershipId' => $membershipId,
            'token' => $this->login($client, $email),
        ];
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
        $client->jsonRequest('POST', '/api/stores', $this->storePayload($code, $name), [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    /** @return array<string, string|null> */
    private function storePayload(string $code, string $name): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'currency' => 'XAF',
            'locale' => 'fr_CG',
        ];
    }

    /** @return array<string, mixed> */
    private function payload(KernelBrowser $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    /** @param list<array<string, mixed>> $assignments
     *  @return array<string, mixed>
     */
    private function assignment(array $assignments, string $roleId): array
    {
        foreach ($assignments as $assignment) {
            if ($roleId === ($assignment['roleId'] ?? null)) {
                return $assignment;
            }
        }

        self::fail(sprintf('Role assignment "%s" was not returned.', $roleId));
    }

    private function assertError(KernelBrowser $client, int $status, string $code): void
    {
        self::assertResponseStatusCodeSame($status);
        self::assertSame($code, $this->payload($client)['code'] ?? null);
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        foreach ([self::OWNER_A_EMAIL, self::OWNER_B_EMAIL] as $email) {
            $organizationId = $connection->fetchOne('SELECT default_organization_id FROM identity_access.users WHERE email = ?', [$email]);
            if (!is_string($organizationId)) {
                continue;
            }
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.store_closures WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [$email]);
            $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [$organizationId]);
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
