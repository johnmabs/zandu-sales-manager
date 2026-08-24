<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrganizationInvitationLifecycleTest extends WebTestCase
{
    private const OWNER_EMAIL = 'invitation-owner@example.com';
    private const MEMBER_EMAIL = 'invitation-member@example.com';
    private const PASSWORD = 'a-strong-password-for-zandu';
    private const CASHIER_ROLE_ID = '00000000-0000-7000-8000-000000000003';

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

    public function testInvitationLifecycleAndNegativeCasesAreEnforcedOverHttp(): void
    {
        $client = self::createClient();
        $owner = $this->registerAndLogin($client, self::OWNER_EMAIL, 'Invitation tenant');
        $member = $this->registerAndLogin($client, self::MEMBER_EMAIL, 'Member home tenant');

        $acceptedInvitation = $this->invite($client, $owner['token'], self::MEMBER_EMAIL);
        $client->request('POST', '/api/invitations/' . $acceptedInvitation['token'] . '/accept', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $member['token'],
        ]);
        self::assertResponseIsSuccessful();
        $accepted = $this->payload($client);
        self::assertSame($owner['organizationId'], $accepted['organizationId']);
        self::assertSame($member['userId'], $accepted['userId']);
        self::assertSame('ACTIVE', $accepted['status']);

        $membership = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT status, role_assignments FROM identity_access.organization_memberships WHERE organization_id = ? AND user_id = ?',
            [$owner['organizationId'], $member['userId']],
        );
        self::assertIsArray($membership);
        self::assertSame('ACTIVE', $membership['status']);
        $assignments = json_decode((string) $membership['role_assignments'], true, flags: JSON_THROW_ON_ERROR);
        self::assertCount(1, $assignments);
        self::assertSame(self::CASHIER_ROLE_ID, $assignments[0]['roleId']);
        self::assertSame('ORGANIZATION', $assignments[0]['scopeType']);

        $client->request('POST', '/api/invitations/' . $acceptedInvitation['token'] . '/accept', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $member['token'],
        ]);
        $this->assertError($client, 422, 'DOMAIN_RULE_VIOLATION');

        $wrongEmail = $this->invite($client, $owner['token'], 'another-member@example.com');
        $client->request('POST', '/api/invitations/' . $wrongEmail['token'] . '/accept', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $member['token'],
        ]);
        $this->assertError($client, 422, 'DOMAIN_RULE_VIOLATION');

        $cancelled = $this->invite($client, $owner['token'], self::MEMBER_EMAIL);
        $client->request('POST', sprintf('/api/member-invitations/%s/cancel', $cancelled['id']), server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $owner['token'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('CANCELLED', $this->payload($client)['status']);
        $client->request('POST', '/api/invitations/' . $cancelled['token'] . '/accept', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $member['token'],
        ]);
        $this->assertError($client, 422, 'DOMAIN_RULE_VIOLATION');

        $expired = $this->invite($client, $owner['token'], self::MEMBER_EMAIL);
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE identity_access.organization_invitations SET expires_at = ? WHERE id = ?',
            [(new \DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:sO'), $expired['id']],
        );
        $client->request('POST', '/api/invitations/' . $expired['token'] . '/accept', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $member['token'],
        ]);
        $this->assertError($client, 422, 'DOMAIN_RULE_VIOLATION');

        $crossTenant = $this->invite($client, $owner['token'], 'cross-tenant@example.com');
        $client->request('POST', sprintf('/api/member-invitations/%s/cancel', $crossTenant['id']), server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $member['token'],
        ]);
        $this->assertError($client, 404, 'NOT_FOUND');
    }

    /** @return array{organizationId: string, userId: string, token: string} */
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

        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $token = $this->payload($client)['token'] ?? null;
        self::assertIsString($token);

        return [
            'organizationId' => $registration['organizationId'],
            'userId' => $registration['userId'],
            'token' => $token,
        ];
    }

    /** @return array{id: string, token: string} */
    private function invite(KernelBrowser $client, string $ownerToken, string $email): array
    {
        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => $email,
            'roleAssignments' => [['roleCode' => 'CASHIER', 'storeIds' => []]],
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $ownerToken]);
        self::assertResponseStatusCodeSame(201);
        $created = $this->payload($client);
        self::assertIsString($created['token'] ?? null);
        $invitation = $created['invitation'] ?? null;
        self::assertTrue(is_array($invitation) || is_string($invitation));
        $id = is_array($invitation) ? ($invitation['id'] ?? null) : basename($invitation);
        self::assertIsString($id);

        return ['id' => $id, 'token' => $created['token']];
    }

    /** @return array<string, mixed> */
    private function payload(KernelBrowser $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    private function assertError(KernelBrowser $client, int $status, string $code): void
    {
        self::assertResponseStatusCodeSame($status);
        self::assertSame($code, $this->payload($client)['code'] ?? null);
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        foreach ([self::OWNER_EMAIL, self::MEMBER_EMAIL] as $email) {
            $organizationId = $connection->fetchOne('SELECT default_organization_id FROM identity_access.users WHERE email = ?', [$email]);
            if (!is_string($organizationId)) {
                continue;
            }
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [$organizationId]);
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
