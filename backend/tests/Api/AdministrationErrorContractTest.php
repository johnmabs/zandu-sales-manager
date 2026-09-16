<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdministrationErrorContractTest extends WebTestCase
{
    private const EMAIL = 'error-contract@example.com';
    private const FOREIGN_ORGANIZATION_ID = '0198e200-147c-72d5-b75a-a936797ff9c8';

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

    public function testAdministrationHttpErrorsMatchThePublishedContract(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/members');
        $this->assertError($client, 401, 'UNAUTHENTICATED');

        $client->request('GET', '/api/members', server: ['HTTP_AUTHORIZATION' => 'Bearer invalid-token']);
        $this->assertError($client, 401, 'UNAUTHENTICATED');

        [$organizationId, $userId, $token] = $this->registerAndLogin($client);

        $client->jsonRequest('PATCH', '/api/organizations/' . $organizationId, [
            'name' => '',
            'countryCode' => 'INVALID',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
            'expectedVersion' => 1,
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $this->assertError($client, 400, 'VALIDATION_ERROR');

        $client->request('POST', '/api/stores', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], content: '{');
        $this->assertError($client, 400, 'VALIDATION_ERROR');

        $this->replaceOwnerRoleWithAccountant($organizationId, $userId);
        $client->jsonRequest('PATCH', '/api/organizations/' . $organizationId, $this->organizationPayload() + ['expectedVersion' => 1], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertError($client, 403, 'FORBIDDEN');
        $this->restoreOwnerRole($organizationId, $userId);

        $this->insertForeignOrganization($userId);
        $client->request('GET', '/api/organizations/' . self::FOREIGN_ORGANIZATION_ID, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertError($client, 404, 'NOT_FOUND');

        $storePayload = [
            'code' => 'DUPLICATE',
            'name' => 'Primary store',
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'currency' => 'XAF',
            'locale' => 'fr_CG',
        ];
        $client->jsonRequest('POST', '/api/stores', $storePayload, ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        self::assertResponseStatusCodeSame(201);
        $client->jsonRequest('POST', '/api/stores', $storePayload, ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $this->assertError($client, 409, 'CONFLICT');

        $client->request('POST', '/api/organizations/' . $organizationId . '/suspend', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        self::assertResponseIsSuccessful();
        $client->request('POST', '/api/organizations/' . $organizationId . '/suspend', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertError($client, 422, 'DOMAIN_RULE_VIOLATION');
    }

    /** @return array{string, string, string} */
    private function registerAndLogin(KernelBrowser $client): array
    {
        $client->jsonRequest('POST', '/api/auth/register', ['email' => self::EMAIL, 'password' => 'a-strong-password-for-zandu'] + $this->organizationPayload());
        self::assertResponseStatusCodeSame(201);
        $registration = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $client->jsonRequest('POST', '/api/auth/login', ['email' => self::EMAIL, 'password' => 'a-strong-password-for-zandu']);
        self::assertResponseIsSuccessful();
        $login = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return [$registration['organizationId'], $registration['userId'], $login['token']];
    }

    /** @return array<string, string> */
    private function organizationPayload(): array
    {
        return [
            'organizationName' => 'Error Contract',
            'name' => 'Error Contract',
            'countryCode' => 'CG',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
        ];
    }

    private function assertError(KernelBrowser $client, int $status, string $code): void
    {
        self::assertResponseStatusCodeSame($status);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($code, $payload['code'] ?? null, (string) $client->getResponse()->getContent());
        self::assertIsString($payload['message'] ?? null);
        self::assertArrayHasKey('correlationId', $payload);
        self::assertIsString($payload['correlationId']);
        self::assertSame(['code', 'message', 'correlationId'], array_keys($payload));
    }

    private function replaceOwnerRoleWithAccountant(string $organizationId, string $userId): void
    {
        $assignments = $this->membershipAssignments($organizationId, $userId);
        $assignments[0]['roleId'] = '00000000-0000-7000-8000-000000000004';
        $this->saveMembershipAssignments($organizationId, $userId, $assignments);
    }

    private function restoreOwnerRole(string $organizationId, string $userId): void
    {
        $assignments = $this->membershipAssignments($organizationId, $userId);
        $assignments[0]['roleId'] = '00000000-0000-7000-8000-000000000001';
        $this->saveMembershipAssignments($organizationId, $userId, $assignments);
    }

    /** @return list<array<string, mixed>> */
    private function membershipAssignments(string $organizationId, string $userId): array
    {
        $json = $this->entityManager->getConnection()->fetchOne(
            'SELECT role_assignments FROM identity_access.organization_memberships WHERE organization_id = ? AND user_id = ?',
            [$organizationId, $userId],
        );

        return json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param list<array<string, mixed>> $assignments */
    private function saveMembershipAssignments(string $organizationId, string $userId, array $assignments): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE identity_access.organization_memberships SET role_assignments = ? WHERE organization_id = ? AND user_id = ?',
            [json_encode($assignments, JSON_THROW_ON_ERROR), $organizationId, $userId],
        );
    }

    private function insertForeignOrganization(string $actorId): void
    {
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version)
VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)
SQL, [self::FOREIGN_ORGANIZATION_ID, 'Foreign organization', $actorId, $actorId]);
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        $organizationId = $connection->fetchOne('SELECT default_organization_id FROM identity_access.users WHERE email = ?', [self::EMAIL]);
        if (is_string($organizationId)) {
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.store_closures WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [self::EMAIL]);
            $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.users WHERE email = ?', [self::EMAIL]);
        }
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::FOREIGN_ORGANIZATION_ID]);
        $this->entityManager->clear();
    }

    private function clearRateLimiters(): void
    {
        $cache = self::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $cache);
        $cache->clear();
    }
}
