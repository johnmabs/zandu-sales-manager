<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\OrganizationId;

final class TenantIsolationWorkflowTest extends WebTestCase
{
    private const OWNER_A_EMAIL = 'tenant-isolation-a@example.com';
    private const OWNER_B_EMAIL = 'tenant-isolation-b@example.com';
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

    public function testForeignTenantResourcesAreHiddenByHttpRepositoriesAndRls(): void
    {
        $client = self::createClient();
        $tenantA = $this->registerAndLogin($client, self::OWNER_A_EMAIL, 'Tenant isolation A');
        $tenantB = $this->registerAndLogin($client, self::OWNER_B_EMAIL, 'Tenant isolation B');
        $storeB = $this->createStore($client, $tenantB['token']);
        $invitationB = $this->createInvitation($client, $tenantB['token']);

        foreach ([
            '/api/organizations/' . $tenantB['organizationId'],
            '/api/stores/' . $storeB['id'],
            '/api/members/' . $tenantB['membershipId'],
        ] as $uri) {
            $client->request('GET', $uri, server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token'],
                'HTTP_ACCEPT' => 'application/json',
            ]);
            $this->assertNotFound($client);
        }

        $client->request('POST', '/api/member-invitations/' . $invitationB['id'] . '/cancel', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $tenantA['token'],
        ]);
        $this->assertNotFound($client);

        $connection = $this->entityManager->getConnection();
        $visibleForeignRows = (new DoctrineTenantTransaction($connection, 'zandu_runtime'))->transactional(
            OrganizationId::fromString($tenantA['organizationId'], new SymfonyUuidFactory()),
            fn(): array => [
                'organizations' => $connection->fetchOne(
                    'SELECT COUNT(*) FROM organization.organizations WHERE id = ?',
                    [$tenantB['organizationId']],
                ),
                'stores' => $connection->fetchOne(
                    'SELECT COUNT(*) FROM organization.stores WHERE id = ?',
                    [$storeB['id']],
                ),
                'memberships' => $connection->fetchOne(
                    'SELECT COUNT(*) FROM identity_access.organization_memberships WHERE id = ?',
                    [$tenantB['membershipId']],
                ),
                'invitations' => $connection->fetchOne(
                    'SELECT COUNT(*) FROM identity_access.organization_invitations WHERE id = ?',
                    [$invitationB['id']],
                ),
            ],
        );
        self::assertSame(['organizations' => 0, 'stores' => 0, 'memberships' => 0, 'invitations' => 0], array_map('intval', $visibleForeignRows));
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

        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $token = $this->payload($client)['token'] ?? null;
        self::assertIsString($token);

        return [
            'organizationId' => $registration['organizationId'],
            'membershipId' => $membershipId,
            'token' => $token,
        ];
    }

    /** @return array<string, mixed> */
    private function createStore(KernelBrowser $client, string $token): array
    {
        $client->jsonRequest('POST', '/api/stores', [
            'code' => 'TENANT-B',
            'name' => 'Tenant B store',
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'currency' => 'XAF',
            'locale' => 'fr_CG',
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    /** @return array{id: string} */
    private function createInvitation(KernelBrowser $client, string $token): array
    {
        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => 'tenant-isolation-invitee@example.com',
            'roleAssignments' => [['roleCode' => 'CASHIER', 'storeIds' => []]],
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        self::assertResponseStatusCodeSame(201);
        $invitation = $this->payload($client)['invitation'] ?? null;
        self::assertTrue(is_array($invitation) || is_string($invitation));
        $id = is_array($invitation) ? ($invitation['id'] ?? null) : basename($invitation);
        self::assertIsString($id);

        return ['id' => $id];
    }

    private function assertNotFound(KernelBrowser $client): void
    {
        self::assertResponseStatusCodeSame(404);
        self::assertSame('NOT_FOUND', $this->payload($client)['code'] ?? null);
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
        foreach ([self::OWNER_A_EMAIL, self::OWNER_B_EMAIL] as $email) {
            $organizationId = $connection->fetchOne(
                'SELECT default_organization_id FROM identity_access.users WHERE email = ?',
                [$email],
            );
            if (!is_string($organizationId)) {
                continue;
            }
            $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [$email]);
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [$organizationId]);
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
