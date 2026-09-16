<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CategoryManagementWorkflowTest extends WebTestCase
{
    private const EMAIL = 'category-owner@example.com';
    private const OTHER_EMAIL = 'category-other-owner@example.com';
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

    public function testOwnerManagesTheTenantCategoryHierarchyAndLifecycle(): void
    {
        $client = self::createClient();
        $token = $this->registerAndLogin($client, self::EMAIL, 'Category Organization');
        $root = $this->create($client, $token, 'Alimentation');
        $child = $this->create($client, $token, 'Boissons', $root['id']);

        $client->request('GET', '/api/categories', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertEqualsCanonicalizing([$root['id'], $child['id']], array_column($this->payload($client), 'id'));

        $client->request('GET', '/api/categories/' . $child['id'], server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame($root['id'], $this->payload($client)['parentCategoryId']);

        $client->jsonRequest('PATCH', '/api/categories/' . $child['id'], ['name' => 'Boissons fraîches', 'expectedVersion' => $child['version']], $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('Boissons fraîches', $this->payload($client)['name']);

        $client->jsonRequest('PATCH', '/api/categories/' . $child['id'], ['name' => 'Écrasement obsolète', 'expectedVersion' => $child['version']], $this->headers($token));
        self::assertResponseStatusCodeSame(409);
        self::assertSame('CONFLICT', $this->payload($client)['code']);

        $client->jsonRequest('POST', '/api/categories/' . $child['id'] . '/move', ['parentCategoryId' => null], $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertNull($this->payload($client)['parentCategoryId']);

        $client->request('POST', '/api/categories/' . $child['id'] . '/deactivate', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('INACTIVE', $this->payload($client)['status']);

        $client->request('POST', '/api/categories/' . $child['id'] . '/activate', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('ACTIVE', $this->payload($client)['status']);

        $client->request('POST', '/api/categories/' . $child['id'] . '/archive', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        $archived = $this->payload($client);
        self::assertSame('ARCHIVED', $archived['status']);
        self::assertSame(6, $archived['version']);

        $otherToken = $this->registerAndLogin($client, self::OTHER_EMAIL, 'Other Category Organization');
        $client->request('GET', '/api/categories/' . $root['id'], server: $this->headers($otherToken));
        self::assertResponseStatusCodeSame(404);
        self::assertSame('NOT_FOUND', $this->payload($client)['code'] ?? null);
    }

    /** @return array<string,mixed> */
    private function create(KernelBrowser $client, string $token, string $name, ?string $parentCategoryId = null): array
    {
        $client->jsonRequest('POST', '/api/categories', [
            'name' => $name,
            'parentCategoryId' => $parentCategoryId,
        ], $this->headers($token));
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    private function registerAndLogin(KernelBrowser $client, string $email, string $organizationName): string
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
        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $token = $this->payload($client)['token'] ?? null;
        self::assertIsString($token);

        return $token;
    }

    /** @return array<string,string> */
    private function headers(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'HTTP_ACCEPT' => 'application/json'];
    }

    /** @return array<string,mixed> */
    private function payload(KernelBrowser $client): array
    {
        $payload = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        foreach ([self::EMAIL, self::OTHER_EMAIL] as $email) {
            $organizationId = $connection->fetchOne('SELECT default_organization_id FROM identity_access.users WHERE email = ?', [$email]);
            if (!is_string($organizationId)) {
                continue;
            }
            $connection->executeStatement('DELETE FROM catalog.categories WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
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
