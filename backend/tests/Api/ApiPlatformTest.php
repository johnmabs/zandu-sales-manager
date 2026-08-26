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

    public function testCategoryManagementOperationsAreDocumented(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $paths = $openApi->getPaths();

        self::assertNotNull($paths->getPath('/api/categories')->getGet());
        self::assertNotNull($paths->getPath('/api/categories')->getPost());
        self::assertNotNull($paths->getPath('/api/categories/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/categories/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/categories/{id}/move')->getPost());
        self::assertNotNull($paths->getPath('/api/categories/{id}/activate')->getPost());
        self::assertNotNull($paths->getPath('/api/categories/{id}/deactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/categories/{id}/archive')->getPost());

        $requestBody = $paths->getPath('/api/categories/{id}')->getPatch()?->getRequestBody();
        self::assertNotNull($requestBody);
        self::assertArrayHasKey('application/json', $requestBody->getContent());
        self::assertArrayHasKey('application/merge-patch+json', $requestBody->getContent());
    }

    public function testProductManagementOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/products')->getGet());
        self::assertNotNull($paths->getPath('/api/products')->getPost());
        self::assertNotNull($paths->getPath('/api/products/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/products/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/products/{id}/activate')->getPost());
        self::assertNotNull($paths->getPath('/api/products/{id}/deactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/products/{id}/reactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/products/{id}/archive')->getPost());
    }

    public function testProductPackagingReadOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings')->getGet());
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{id}')->getGet());
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

    public function testAdministrationErrorContractIsDocumented(): void
    {
        self::bootKernel();
        $operation = self::getContainer()->get(OpenApiFactoryInterface::class)([])
            ->getPaths()->getPath('/api/members/{id}')->getGet();

        self::assertNotNull($operation);
        $contracts = [
            400 => 'VALIDATION_ERROR',
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'DOMAIN_RULE_VIOLATION',
        ];
        foreach ($contracts as $status => $code) {
            self::assertArrayHasKey((string) $status, $operation->getResponses());
            $content = $operation->getResponses()[(string) $status]->getContent();
            self::assertArrayHasKey('application/json', $content);
            self::assertSame([$code], $content['application/json']->getSchema()['properties']['code']['enum']);
        }
    }
}
