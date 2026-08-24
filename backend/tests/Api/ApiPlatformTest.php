<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ApiPlatformTest extends KernelTestCase
{
    public function testOpenApiDocumentationCanBeGenerated(): void
    {
        self::bootKernel();

        $factory = self::getContainer()->get(OpenApiFactoryInterface::class);
        self::assertInstanceOf(OpenApiFactoryInterface::class, $factory);

        $openApi = $factory([]);

        self::assertSame('Zandu Sales Manager API', $openApi->getInfo()->getTitle());
        self::assertSame('0.1.0', $openApi->getInfo()->getVersion());
    }

    public function testOrganizationAdministrationOperationsAreDocumented(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $paths = $openApi->getPaths();

        self::assertNull($paths->getPath('/api/organizations'));
        self::assertNotNull($paths->getPath('/api/organizations/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/organizations/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/organizations/{id}/suspend')->getPost());
        self::assertNotNull($paths->getPath('/api/organizations/{id}/reactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/organizations/{id}/closure-request')->getPost());
    }

    public function testOrganizationPatchAcceptsJsonAndMergePatchJson(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $requestBody = $openApi->getPaths()
            ->getPath('/api/organizations/{id}')
            ->getPatch()
            ?->getRequestBody();

        self::assertNotNull($requestBody);
        self::assertArrayHasKey('application/json', $requestBody->getContent());
        self::assertArrayHasKey('application/merge-patch+json', $requestBody->getContent());
    }

    public function testStoreAdministrationOperationsAreDocumented(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $paths = $openApi->getPaths();

        self::assertNotNull($paths->getPath('/api/stores')->getGet());
        self::assertNotNull($paths->getPath('/api/stores')->getPost());
        self::assertNotNull($paths->getPath('/api/stores/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/stores/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/stores/{id}/suspend')->getPost());
        self::assertNotNull($paths->getPath('/api/stores/{id}/reactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/stores/{id}/closure-request')->getPost());
    }

    public function testStorePatchAcceptsJsonAndMergePatchJson(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $requestBody = $openApi->getPaths()
            ->getPath('/api/stores/{id}')
            ->getPatch()
            ?->getRequestBody();

        self::assertNotNull($requestBody);
        self::assertArrayHasKey('application/json', $requestBody->getContent());
        self::assertArrayHasKey('application/merge-patch+json', $requestBody->getContent());
    }

    public function testOrganizationInvitationOperationsAreDocumentedWithoutTokenHash(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $paths = $openApi->getPaths();

        self::assertNotNull($paths->getPath('/api/member-invitations')->getPost());
        self::assertNotNull($paths->getPath('/api/member-invitations/{id}/cancel')->getPost());
        self::assertNotNull($paths->getPath('/api/invitations/{token}/accept')->getPost());
        self::assertStringNotContainsString('tokenHash', json_encode($openApi, JSON_THROW_ON_ERROR));
    }

    public function testMembershipAdministrationOperationsAreDocumented(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $paths = $openApi->getPaths();

        self::assertNotNull($paths->getPath('/api/members')->getGet());
        self::assertNotNull($paths->getPath('/api/members/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/members/{id}/suspend')->getPost());
        self::assertNotNull($paths->getPath('/api/members/{id}/reactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/members/{id}/revoke')->getPost());
    }

    public function testRoleAssignmentOperationsAreDocumented(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $paths = $openApi->getPaths();

        self::assertNotNull($paths->getPath('/api/roles')->getGet());
        self::assertNotNull($paths->getPath('/api/members/{id}/role-assignments')->getPost());
        self::assertNotNull($paths->getPath('/api/members/{id}/role-assignments/{assignmentId}')->getDelete());
    }
}
