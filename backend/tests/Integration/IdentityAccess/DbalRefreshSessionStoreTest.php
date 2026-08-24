<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\IdentityAccess;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\DbalRefreshSessionStore;
use Zandu\Platform\Auth\Refresh\RefreshSession;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SessionId;

final class DbalRefreshSessionStoreTest extends KernelTestCase
{
    private const ORGANIZATION_ID = '0198e300-147c-72d5-b75a-a936797ff9c8';
    private const SESSION_ID = '0198e301-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198e302-147c-72d5-b75a-a936797ff9c8';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->cleanup();
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version)
VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)
SQL, [self::ORGANIZATION_ID, 'Refresh tenant', self::ACTOR_ID, self::ACTOR_ID]);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testSessionTenantAndAuthorizationVersionRoundTrip(): void
    {
        $factory = new SymfonyUuidFactory();
        $store = new DbalRefreshSessionStore($this->entityManager->getConnection(), $factory);
        $store->add(new RefreshSession(
            SessionId::fromString(self::SESSION_ID, $factory),
            'refresh-member@example.com',
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            7,
            hash('sha256', 'refresh-token'),
            [],
            new DateTimeImmutable('2026-09-24T12:00:00+00:00'),
        ));

        $session = $store->getForUpdate(SessionId::fromString(self::SESSION_ID, $factory));

        self::assertNotNull($session);
        self::assertSame(self::ORGANIZATION_ID, $session->organizationId()->toString());
        self::assertSame(7, $session->authorizationVersion());
        self::assertSame('refresh-member@example.com', $session->userIdentifier());
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE id = ?', [self::SESSION_ID]);
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION_ID]);
        $this->entityManager->clear();
    }
}
