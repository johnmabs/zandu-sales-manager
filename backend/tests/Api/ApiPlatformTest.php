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

    public function testEveryInteractivePatchRequiresAnExpectedVersion(): void
    {
        self::bootKernel();
        $openApi = self::getContainer()->get(OpenApiFactoryInterface::class)([]);
        $schemas = $openApi->getComponents()->getSchemas();
        $patchCount = 0;

        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            $patch = $pathItem->getPatch();
            if (null === $patch) {
                continue;
            }
            ++$patchCount;
            $requestBody = $patch->getRequestBody();
            self::assertNotNull($requestBody, $path);
            $schema = $requestBody->getContent()['application/merge-patch+json']->getSchema();
            $reference = $schema['$ref'] ?? null;
            self::assertIsString($reference, $path);
            $component = $schemas[basename($reference)] ?? null;
            self::assertInstanceOf(\ArrayObject::class, $component, $path);
            self::assertArrayHasKey('expectedVersion', $component['properties'], $path);
            self::assertContains('expectedVersion', $component['required'] ?? [], $path);
        }

        self::assertSame(13, $patchCount);
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

    public function testCurrentSessionProjectionIsDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/session')->getGet());
    }

    public function testAuthenticationLifecycleIsDocumentedWithItsActualWireContract(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        $login = $paths->getPath('/api/auth/login')->getPost();
        $refresh = $paths->getPath('/api/auth/refresh')->getPost();
        $logout = $paths->getPath('/api/auth/logout')->getPost();
        self::assertNotNull($login);
        self::assertNotNull($refresh);
        self::assertNotNull($logout);
        self::assertArrayHasKey('token', $login->getResponses()['200']->getContent()['application/json']->getSchema()['properties']);
        self::assertArrayHasKey('token', $refresh->getResponses()['200']->getContent()['application/json']->getSchema()['properties']);
        self::assertArrayNotHasKey('refreshToken', $login->getResponses()['200']->getContent()['application/json']->getSchema()['properties']);
        self::assertNull($refresh->getRequestBody());
        self::assertNull($logout->getRequestBody());
        self::assertArrayHasKey('204', $logout->getResponses());
        self::assertNull($logout->getResponses()['204']->getContent());
    }

    public function testMalformedLoginRouteIsAbsentFromOpenApi(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNull($paths->getPath('api_auth_login'));
        self::assertNotNull($paths->getPath('/api/auth/login')->getPost());
    }

    public function testOnboardingIsDocumentedWithItsActualWireContracts(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();
        $registration = $paths->getPath('/api/auth/register')->getPost();
        $invitation = $paths->getPath('/api/auth/invitations/{token}/register')->getPost();

        self::assertNotNull($registration);
        self::assertNotNull($invitation);
        self::assertSame('auth_register', $registration->getOperationId());
        self::assertSame('auth_invitation_register', $invitation->getOperationId());
        self::assertSame([], $registration->getSecurity());
        self::assertSame([], $invitation->getSecurity());
        self::assertSame(
            ['email', 'password', 'organizationName', 'countryCode', 'defaultCurrency', 'defaultTimeZone', 'defaultLocale'],
            $registration->getRequestBody()->getContent()['application/json']->getSchema()['required'],
        );
        self::assertSame(['password'], $invitation->getRequestBody()->getContent()['application/json']->getSchema()['required']);
        self::assertSame('token', $invitation->getParameters()[0]->getName());
        self::assertTrue($invitation->getParameters()[0]->getRequired());
        foreach ([$registration, $invitation] as $operation) {
            self::assertArrayHasKey('201', $operation->getResponses());
            self::assertArrayHasKey('409', $operation->getResponses());
            self::assertArrayHasKey('422', $operation->getResponses());
            self::assertArrayHasKey('userId', $operation->getResponses()['201']->getContent()['application/json']->getSchema()['properties']);
            self::assertArrayHasKey('organizationId', $operation->getResponses()['201']->getContent()['application/json']->getSchema()['properties']);
        }
    }

    public function testStockTransferWorkflowIsDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/stock-transfers')->getGet());
        self::assertNotNull($paths->getPath('/api/stock-transfers')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-transfers/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/stock-transfers/{id}/lines')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-transfers/{id}/lines/{lineId}')->getPatch());
        self::assertNotNull($paths->getPath('/api/stock-transfers/{id}/lines/{lineId}')->getDelete());
        self::assertNotNull($paths->getPath('/api/stock-transfers/{id}/ship')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-transfers/{id}/receive')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-transfers/{id}/cancel')->getPost());
    }

    public function testStockCountWorkflowIsDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/stock-counts')->getGet());
        self::assertNotNull($paths->getPath('/api/stores/{storeId}/stock-counts')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-counts/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/stock-counts/{id}/start')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-counts/{id}/counts')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-counts/{id}/counts/batch')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-counts/{id}/finalization')->getPost());
        self::assertNotNull($paths->getPath('/api/stock-counts/{id}/cancel')->getPost());
    }

    public function testPurchasingWorkflowsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/suppliers')->getGet());
        self::assertNotNull($paths->getPath('/api/suppliers')->getPost());
        self::assertNotNull($paths->getPath('/api/suppliers/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/suppliers/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/suppliers/{id}/activate')->getPost());
        self::assertNotNull($paths->getPath('/api/suppliers/{id}/deactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/suppliers/{id}/archive')->getPost());

        self::assertNotNull($paths->getPath('/api/purchase-orders')->getGet());
        self::assertNotNull($paths->getPath('/api/stores/{storeId}/purchase-orders')->getPost());
        self::assertNotNull($paths->getPath('/api/purchase-orders/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/purchase-orders/{id}/lines')->getPost());
        self::assertNotNull($paths->getPath('/api/purchase-orders/{id}/lines/{lineId}')->getPatch());
        self::assertNotNull($paths->getPath('/api/purchase-orders/{id}/lines/{lineId}')->getDelete());
        self::assertNotNull($paths->getPath('/api/purchase-orders/{id}/confirm')->getPost());
        self::assertNotNull($paths->getPath('/api/purchase-orders/{id}/cancel')->getPost());
        self::assertNotNull($paths->getPath('/api/purchase-orders/{id}/close')->getPost());

        self::assertNotNull($paths->getPath('/api/goods-receipts')->getGet());
        self::assertNotNull($paths->getPath('/api/stores/{storeId}/goods-receipts')->getPost());
        self::assertNotNull($paths->getPath('/api/goods-receipts/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/goods-receipts/{id}/lines')->getPost());
        self::assertNotNull($paths->getPath('/api/goods-receipts/{id}/lines/{lineId}')->getPatch());
        self::assertNotNull($paths->getPath('/api/goods-receipts/{id}/lines/{lineId}')->getDelete());
        self::assertNotNull($paths->getPath('/api/goods-receipts/{id}/post')->getPost());
        self::assertNotNull($paths->getPath('/api/goods-receipts/{id}/cancel')->getPost());

        self::assertNotNull($paths->getPath('/api/goods-receipts/{id}/corrections')->getPost());
        self::assertNotNull($paths->getPath('/api/goods-receipt-corrections/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/goods-receipt-corrections/{id}/post')->getPost());
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
        $cancelClosure = $paths->getPath('/api/stores/{id}/closure-request/cancel')->getPost();
        self::assertNotNull($cancelClosure);
        self::assertNull($cancelClosure->getRequestBody());
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

        $requestBody = $paths->getPath('/api/categories/{id}')->getPatch()->getRequestBody();
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
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings')->getPost());
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{id}/deactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{id}/archive')->getPost());
    }

    public function testProductBarcodeOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{packagingId}/barcodes')->getPost());
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{packagingId}/barcodes/{id}')->getDelete());
        self::assertNotNull($paths->getPath('/api/catalog/barcodes/{barcode}')->getGet());
    }

    public function testPriceListReadOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();
        self::assertNotNull($paths->getPath('/api/price-lists')->getGet());
        self::assertNotNull($paths->getPath('/api/price-lists/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/price-lists')->getPost());
        self::assertNotNull($paths->getPath('/api/price-lists/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/price-lists/{id}/activate')->getPost());
        self::assertNotNull($paths->getPath('/api/price-lists/{id}/deactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/price-lists/{id}/archive')->getPost());
    }

    public function testProductPriceReadOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();
        self::assertNotNull($paths->getPath('/api/product-prices')->getGet());
        self::assertNotNull($paths->getPath('/api/product-prices')->getPost());
        self::assertNotNull($paths->getPath('/api/product-prices/{id}/activate')->getPost());
        self::assertNotNull($paths->getPath('/api/product-prices/{id}/deactivate')->getPost());
        self::assertNotNull($paths->getPath('/api/product-prices/{id}/archive')->getPost());
        self::assertNotNull($paths->getPath('/api/product-prices/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/product-prices/{id}')->getPatch());
        self::assertNotNull($paths->getPath('/api/products/{productId}/packagings/{packagingId}/effective-price')->getGet());
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

    public function testCatalogPricingOperationsExposeStandardErrorContract(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();
        $operations = [
            $paths->getPath('/api/products/{productId}/packagings/{packagingId}/effective-price')->getGet(),
            $paths->getPath('/api/product-prices/{id}')->getPatch(),
        ];
        foreach ($operations as $operation) {
            self::assertNotNull($operation);
            foreach ([400 => 'VALIDATION_ERROR', 401 => 'UNAUTHENTICATED', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 409 => 'CONFLICT', 422 => 'DOMAIN_RULE_VIOLATION'] as $status => $code) {
                self::assertArrayHasKey((string) $status, $operation->getResponses());
                self::assertSame([$code], $operation->getResponses()[(string) $status]->getContent()['application/json']->getSchema()['properties']['code']['enum']);
            }
        }
    }

    public function testCashSalesOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/stores/{storeId}/sales')->getPost());
        self::assertNotNull($paths->getPath('/api/sales/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/sales/{id}/lines')->getPost());
        self::assertNotNull($paths->getPath('/api/sales/{id}/lines/{lineId}')->getPatch());
        self::assertNotNull($paths->getPath('/api/sales/{id}/lines/{lineId}')->getDelete());
        self::assertNotNull($paths->getPath('/api/sales/{id}/cancel')->getPost());
        self::assertNotNull($paths->getPath('/api/sales/{id}/complete')->getPost());
        self::assertNotNull($paths->getPath('/api/sales/{id}/receipt')->getGet());
    }

    public function testInventoryValuationBootstrapIsDocumented(): void
    {
        self::bootKernel();
        $operation = self::getContainer()->get(OpenApiFactoryInterface::class)([])
            ->getPaths()
            ->getPath('/api/stores/{storeId}/inventory-valuations/{productId}/initialize')
            ->getPost();

        self::assertNotNull($operation);
        self::assertNotNull($operation->getRequestBody());
        self::assertArrayHasKey('application/json', $operation->getRequestBody()->getContent());
    }

    public function testUnitOfMeasureReadOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/units-of-measure')->getGet());
        self::assertNotNull($paths->getPath('/api/units-of-measure/{id}')->getGet());
    }

    public function testInventoryValuationReadOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/stores/{storeId}/inventory-valuations')->getGet());
        self::assertNotNull($paths->getPath('/api/stores/{storeId}/inventory-valuations/{productId}')->getGet());
        self::assertNotNull($paths->getPath('/api/stores/{storeId}/inventory-valuations/{productId}/movements')->getGet());
    }

    public function testInventoryMovementCostInputsAreDocumented(): void
    {
        self::bootKernel();
        $schemas = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getComponents()->getSchemas();
        $initialize = json_decode(json_encode($schemas['StockResource.InitializeStockInput'], JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $adjust = json_decode(json_encode($schemas['StockResource.AdjustStockInput'], JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('unitCost', $initialize['properties']);
        self::assertArrayHasKey('unitCost', $adjust['properties']);
    }

    public function testReturnSaleOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/sales/{saleId}/returns')->getPost());
        self::assertNotNull($paths->getPath('/api/sales/{saleId}/returns')->getGet());
        self::assertNotNull($paths->getPath('/api/returns/{id}/lines')->getPost());
        self::assertNotNull($paths->getPath('/api/returns/{id}/complete')->getPost());
        self::assertNotNull($paths->getPath('/api/returns/{id}/cancel')->getPost());
        self::assertNotNull($paths->getPath('/api/returns/{id}')->getGet());
    }

    public function testPurchaseReturnOperationsAreDocumented(): void
    {
        self::bootKernel();
        $paths = self::getContainer()->get(OpenApiFactoryInterface::class)([])->getPaths();

        self::assertNotNull($paths->getPath('/api/stores/{storeId}/purchase-returns')->getPost());
        self::assertNotNull($paths->getPath('/api/purchase-returns/{id}')->getGet());
        self::assertNotNull($paths->getPath('/api/purchase-returns/{id}/lines')->getPost());
        self::assertNotNull($paths->getPath('/api/purchase-returns/{id}/ship')->getPost());
        self::assertNotNull($paths->getPath('/api/purchase-returns/{id}/cancel')->getPost());
    }
}
