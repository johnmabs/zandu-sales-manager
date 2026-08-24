<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\InvitationTokenService;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineOrganizationInvitationRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;

final class UserOnboardingApiTest extends WebTestCase
{
    private const EMAIL = 'onboarding-api@example.com';
    private const FOREIGN_ORGANIZATION_ID = '0198e100-147c-72d5-b75a-a936797ff9c8';
    private const INVITED_EMAIL = 'invited-onboarding-api@example.com';
    private const INVITED_ORGANIZATION_ID = '0198e101-147c-72d5-b75a-a936797ff9c8';
    private const INVITATION_ID = '0198e102-147c-72d5-b75a-a936797ff9c8';
    private const INVITER_ID = '0198e103-147c-72d5-b75a-a936797ff9c8';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->cleanup();
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->cleanup();
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    public function testVisitorCanBootstrapAnActiveOrganizationThenUseItAsItsOwner(): void
    {
        $client = self::createClient();
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => self::EMAIL,
            'password' => 'a-strong-password-for-zandu',
            'organizationName' => 'Onboarding API',
            'countryCode' => 'CG',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
        ]);

        self::assertResponseStatusCodeSame(201);
        $registration = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($registration);
        self::assertArrayHasKey('userId', $registration);
        self::assertArrayHasKey('organizationId', $registration);

        $organization = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT name, status FROM organization.organizations WHERE id = ?',
            [$registration['organizationId']],
        );
        self::assertSame(['name' => 'Onboarding API', 'status' => 'ACTIVE'], $organization);

        $membership = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT status, role_assignments FROM identity_access.organization_memberships WHERE organization_id = ? AND user_id = ?',
            [$registration['organizationId'], $registration['userId']],
        );
        self::assertIsArray($membership);
        self::assertSame('ACTIVE', $membership['status']);
        $assignments = json_decode((string) $membership['role_assignments'], true, flags: JSON_THROW_ON_ERROR);
        self::assertCount(1, $assignments);
        self::assertSame('00000000-0000-7000-8000-000000000001', $assignments[0]['roleId']);
        self::assertSame('ORGANIZATION', $assignments[0]['scopeType']);

        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => self::EMAIL,
            'password' => 'a-strong-password-for-zandu',
        ]);

        self::assertResponseIsSuccessful();
        $login = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($login);
        self::assertArrayHasKey('token', $login);
        self::assertArrayHasKey('refreshToken', $login);

        $client->request('GET', '/api/organizations/' . $registration['organizationId'], server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $login['token'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('ACTIVE', json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['status']);

        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version)
VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)
SQL, [self::FOREIGN_ORGANIZATION_ID, 'Foreign organization', $registration['userId'], $registration['userId']]);

        $client->request('GET', '/api/organizations/' . self::FOREIGN_ORGANIZATION_ID, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $login['token'],
        ]);
        self::assertResponseStatusCodeSame(404);
        self::assertSame('NOT_FOUND', json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['code']);

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE identity_access.organization_memberships SET authorization_version = authorization_version + 1 WHERE organization_id = ? AND user_id = ?',
            [$registration['organizationId'], $registration['userId']],
        );
        $client->jsonRequest('POST', '/api/auth/refresh', ['refreshToken' => $login['refreshToken']]);
        self::assertResponseStatusCodeSame(401);
        self::assertSame(
            'INVALID_REFRESH_TOKEN',
            json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['code'],
        );
    }

    public function testInvitedVisitorWithoutAccountCanRegisterThenLogin(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString(self::INVITED_ORGANIZATION_ID, $factory);
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version)
VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)
SQL, [self::INVITED_ORGANIZATION_ID, 'Invitation onboarding', self::INVITER_ID, self::INVITER_ID]);
        $tokens = self::getContainer()->get(InvitationTokenService::class);
        $issued = $tokens->issue($organizationId);
        $invitation = OrganizationInvitation::invite(
            OrganizationInvitationId::fromString(self::INVITATION_ID, $factory),
            $organizationId,
            InvitationEmail::fromString(self::INVITED_EMAIL),
            ActorId::fromString(self::INVITER_ID, $factory),
            $issued->tokenHash(),
            new DateTimeImmutable('+1 day'),
            [IntendedRoleAssignment::forRole(RoleCode::CASHIER)],
            new DateTimeImmutable('now'),
        );
        $repository = new DoctrineOrganizationInvitationRepository($this->entityManager, $factory);
        (new DoctrineTenantTransaction($this->entityManager->getConnection(), 'zandu_runtime'))
            ->transactional($organizationId, fn() => $repository->save($invitation));
        self::ensureKernelShutdown();

        $client = self::createClient();
        $client->jsonRequest('POST', '/api/auth/invitations/' . $issued->reveal() . '/register', [
            'password' => 'a-strong-password-for-invitee',
        ]);
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('POST', '/api/auth/login', [
            'email' => self::INVITED_EMAIL,
            'password' => 'a-strong-password-for-invitee',
        ]);
        self::assertResponseIsSuccessful();
        $login = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($login);
        self::assertArrayHasKey('token', $login);
    }

    public function testUnknownInvitationTokenReturnsAStablePublicError(): void
    {
        $client = self::createClient();
        $client->jsonRequest(
            'POST',
            '/api/auth/invitations/' . self::INVITED_ORGANIZATION_ID . '.AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA/register',
            ['password' => 'a-strong-password-for-invitee'],
        );

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"code":"INVALID_INVITATION_REGISTRATION","message":"The invitation is invalid or inactive."}',
            (string) $client->getResponse()->getContent(),
        );
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        $organizationId = $connection->fetchOne('SELECT default_organization_id FROM identity_access.users WHERE email = ?', [self::EMAIL]);
        if (is_string($organizationId)) {
            $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [self::EMAIL]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.users WHERE email = ?', [self::EMAIL]);
        }
        $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [self::INVITED_EMAIL]);
        $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [self::INVITED_ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [self::INVITED_ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM identity_access.users WHERE email = ?', [self::INVITED_EMAIL]);
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::INVITED_ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::FOREIGN_ORGANIZATION_ID]);
        $this->entityManager->clear();
    }
}
