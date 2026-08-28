<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\{PreserveGlobalState, RunTestsInSeparateProcesses};
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class StoreScopedAuthorizationWorkflowTest extends WebTestCase
{
    private const OWNER_EMAIL = 'scope-owner@example.com';
    private const MANAGER_EMAIL = 'scope-manager@example.com';
    private const CASHIER_EMAIL = 'scope-cashier@example.com';
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

    public function testStoreManagerCanUpdateOnlyTheStoreInItsScope(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerOwnerAndLogin($client);
        $storeA = $this->createStore($client, $ownerToken, 'STORE-A', 'Store A');
        $storeB = $this->createStore($client, $ownerToken, 'STORE-B', 'Store B');

        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => self::MANAGER_EMAIL,
            'roleAssignments' => [[
                'roleCode' => 'STORE_MANAGER',
                'storeIds' => [$storeA['id']],
            ]],
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $ownerToken]);
        self::assertResponseStatusCodeSame(201);
        $invitationToken = $this->payload($client)['token'] ?? null;
        self::assertIsString($invitationToken);

        $client->jsonRequest('POST', '/api/auth/invitations/' . $invitationToken . '/register', [
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);
        $managerToken = $this->login($client, self::MANAGER_EMAIL);

        $this->updateStore($client, $managerToken, $storeA['id'], 'Store A managed');
        self::assertResponseIsSuccessful();
        self::assertSame('Store A managed', $this->payload($client)['name']);

        $this->updateStore($client, $managerToken, $storeB['id'], 'Store B forbidden');
        self::assertResponseStatusCodeSame(403);
        self::assertSame('FORBIDDEN', $this->payload($client)['code'] ?? null);
        self::assertSame(
            'Store B',
            $this->entityManager->getConnection()->fetchOne('SELECT name FROM organization.stores WHERE id = ?', [$storeB['id']]),
        );
    }

    public function testInventoryAndCashOperationsEnforcePermissionsAndStoreScope(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerOwnerAndLogin($client);
        $storeA = $this->createStore($client, $ownerToken, 'OPS-A', 'Operations A');
        $storeB = $this->createStore($client, $ownerToken, 'OPS-B', 'Operations B');
        $registerA = $this->createCashRegister($client, $ownerToken, $storeA['id'], 'REG-A');
        $registerB = $this->createCashRegister($client, $ownerToken, $storeB['id'], 'REG-B');

        $managerToken = $this->inviteAndRegister($client, $ownerToken, self::MANAGER_EMAIL, 'STORE_MANAGER', $storeA['id']);

        $client->jsonRequest('POST', '/api/stores/' . $storeA['id'] . '/cash-registers', [
            'code' => 'FORBIDDEN',
            'name' => 'Forbidden register',
        ], $this->headers($managerToken));
        self::assertResponseStatusCodeSame(403);

        $client->request('POST', '/api/stores/' . $storeA['id'] . '/cash-registers/' . $registerA['id'] . '/deactivate', server: $this->headers($managerToken));
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/stores/' . $storeB['id'] . '/cash-registers/' . $registerB['id'] . '/deactivate', server: $this->headers($managerToken));
        self::assertResponseStatusCodeSame(403);

        $productId = '019a0000-0000-7000-8000-000000000001';
        $client->jsonRequest('POST', '/api/stores/' . $storeB['id'] . '/stocks/' . $productId . '/adjust', [
            'delta' => '1',
            'reason' => 'Scope verification',
        ], $this->headers($managerToken));
        self::assertResponseStatusCodeSame(403);

        $client->jsonRequest('POST', '/api/stores/' . $storeA['id'] . '/stocks/' . $productId . '/initialize', [
            'quantity' => '1',
            'unitCost' => '1000',
        ], $this->headers($managerToken));
        self::assertResponseStatusCodeSame(403);

        $cashierToken = $this->inviteAndRegister($client, $ownerToken, self::CASHIER_EMAIL, 'CASHIER', $storeA['id']);
        $client->request('POST', '/api/stores/' . $storeA['id'] . '/cash-registers/' . $registerA['id'] . '/activate', server: $this->headers($ownerToken));
        self::assertResponseIsSuccessful();

        $sessionA = $this->openCashSession($client, $cashierToken, $storeA['id'], $registerA['id'], '100');
        $client->jsonRequest('POST', '/api/stores/' . $storeA['id'] . '/cash-sessions/' . $sessionA['id'] . '/cash-in', [
            'amount' => '25',
            'currency' => 'XAF',
            'reason' => 'Opening top-up',
        ], $this->headers($cashierToken));
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('POST', '/api/stores/' . $storeA['id'] . '/cash-sessions/' . $sessionA['id'] . '/cash-out', [
            'amount' => '5',
            'currency' => 'XAF',
            'reason' => 'Petty cash',
        ], $this->headers($cashierToken));
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('POST', '/api/stores/' . $storeA['id'] . '/cash-sessions/' . $sessionA['id'] . '/close', [
            'amount' => '120',
            'currency' => 'XAF',
        ], $this->headers($cashierToken));
        self::assertResponseIsSuccessful();
        self::assertSame('120.000000000000', $this->payload($client)['expectedClosingAmount']);

        $client->jsonRequest('POST', '/api/stores/' . $storeB['id'] . '/cash-registers/' . $registerB['id'] . '/sessions/open', [
            'amount' => '0',
            'currency' => 'XAF',
        ], $this->headers($cashierToken));
        self::assertResponseStatusCodeSame(403);

        $sessionB = $this->openCashSession($client, $ownerToken, $storeB['id'], $registerB['id'], '0');
        $client->jsonRequest('POST', '/api/stores/' . $storeB['id'] . '/cash-sessions/' . $sessionB['id'] . '/cash-in', [
            'amount' => '50',
            'currency' => 'XAF',
            'reason' => 'Other store cash',
        ], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/stores/' . $storeA['id'] . '/cash-sessions/' . $sessionB['id'] . '/movements', server: $this->headers($cashierToken));
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->payload($client));
    }

    public function testCashierCanCreateReadAndCancelDraftSaleOnlyInItsStoreScope(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerOwnerAndLogin($client);
        $storeA = $this->createStore($client, $ownerToken, 'SALE-A', 'Sales A');
        $storeB = $this->createStore($client, $ownerToken, 'SALE-B', 'Sales B');
        $cashierToken = $this->inviteAndRegister($client, $ownerToken, self::CASHIER_EMAIL, 'CASHIER', $storeA['id']);

        $client->request('POST', '/api/stores/' . $storeA['id'] . '/sales', server: $this->headers($cashierToken));
        self::assertResponseStatusCodeSame(201);
        $sale = $this->payload($client);
        self::assertSame('DRAFT', $sale['status']);
        self::assertSame('XAF', $sale['currency']);

        $client->request('GET', '/api/sales/' . $sale['id'], server: $this->headers($cashierToken));
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/sales/' . $sale['id'] . '/receipt', server: $this->headers($cashierToken));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('SALE_NOT_COMPLETED', $this->payload($client)['code']);

        $client->request('POST', '/api/sales/' . $sale['id'] . '/cancel', server: $this->headers($cashierToken));
        self::assertResponseIsSuccessful();
        self::assertSame('CANCELLED', $this->payload($client)['status']);

        $client->request('POST', '/api/stores/' . $storeB['id'] . '/sales', server: $this->headers($cashierToken));
        self::assertResponseStatusCodeSame(403);
    }

    public function testCompleteCashSaleHttpWorkflowIsAtomicAndReplaySafe(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerOwnerAndLogin($client);
        $store = $this->createStore($client, $ownerToken, 'M2', 'M2 Store');
        $register = $this->createCashRegister($client, $ownerToken, $store['id'], 'M2-REG');
        $session = $this->openCashSession($client, $ownerToken, $store['id'], $register['id'], '0');
        $connection = $this->entityManager->getConnection();
        $organizationId = $connection->fetchOne('SELECT default_organization_id FROM identity_access.users WHERE email = ?', [self::OWNER_EMAIL]);
        $actorId = $connection->fetchOne('SELECT actor_id FROM identity_access.users WHERE email = ?', [self::OWNER_EMAIL]);
        self::assertIsString($organizationId);
        self::assertIsString($actorId);
        $unitId = '019a1000-0000-7000-8000-000000000001';
        $productId = '019a1000-0000-7000-8000-000000000002';
        $packagingId = '019a1000-0000-7000-8000-000000000003';
        $priceListId = '019a1000-0000-7000-8000-000000000004';
        $productPriceId = '019a1000-0000-7000-8000-000000000005';

        $connection->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',0,'HalfUp','ACTIVE',1)", [$unitId, $organizationId]);
        $connection->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,'M2-SKU','Produit M2','ACTIVE','PHYSICAL',?,TRUE,date_trunc('second',NOW()),?,date_trunc('second',NOW()),?,1)", [$productId, $organizationId, $unitId, $actorId, $actorId]);
        $connection->executeStatement("INSERT INTO catalog.product_packagings (id,organization_id,product_id,base,code,name,unit_id,conversion_factor,precision,minimum_quantity,quantity_increment,allowed_for_sale,allowed_for_purchase,status,created_at,created_by,version) VALUES (?,?,?,TRUE,'EA','Article',?,1,0,1,1,TRUE,TRUE,'ACTIVE',date_trunc('second',NOW()),?,1)", [$packagingId, $organizationId, $productId, $unitId, $actorId]);
        $connection->executeStatement("INSERT INTO pricing.price_lists (id,organization_id,code,name,currency,status,scope,priority,created_at,created_by,version) VALUES (?,?,'M2','Tarif M2','XAF','ACTIVE','ORGANIZATION',100,date_trunc('second',NOW()),?,1)", [$priceListId, $organizationId, $actorId]);
        $connection->executeStatement("INSERT INTO pricing.product_prices (id,organization_id,price_list_id,product_id,packaging_id,amount,currency,status,created_at,created_by,version) VALUES (?,?,?,?,?,1500,'XAF','ACTIVE',date_trunc('second',NOW()),?,1)", [$productPriceId, $organizationId, $priceListId, $productId, $packagingId, $actorId]);

        $client->jsonRequest('POST', '/api/stores/' . $store['id'] . '/stocks/' . $productId . '/initialize', ['quantity' => '10', 'unitCost' => '400'], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        $client->request('POST', '/api/stores/' . $store['id'] . '/sales', server: $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        $sale = $this->payload($client);
        $client->jsonRequest('POST', '/api/sales/' . $sale['id'] . '/lines', ['productId' => $productId, 'productPackagingId' => $packagingId, 'quantity' => '2'], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        self::assertSame('3000.000000000000', $this->payload($client)['total']);

        $payload = [
            'cashSessionId' => $session['id'],
            'payment' => ['method' => 'CASH', 'amount' => ['amount' => '3000', 'currency' => 'XAF']],
            'tenderedAmount' => ['amount' => '5000', 'currency' => 'XAF'],
        ];
        $headers = [...$this->headers($ownerToken), 'HTTP_IDEMPOTENCY_KEY' => 'm2-sale-completion'];
        $client->jsonRequest('POST', '/api/sales/' . $sale['id'] . '/complete', $payload, $headers);
        self::assertResponseIsSuccessful();
        $result = $this->payload($client);
        self::assertSame('COMPLETED', $result['status']);
        self::assertSame('2000', $result['changeAmount']['amount']);

        $client->jsonRequest('POST', '/api/sales/' . $sale['id'] . '/complete', $payload, $headers);
        self::assertResponseIsSuccessful();
        self::assertSame($result['paymentId'], $this->payload($client)['paymentId']);
        $client->request('GET', '/api/sales/' . $sale['id'] . '/receipt', server: $this->headers($ownerToken));
        self::assertResponseIsSuccessful();
        self::assertSame('COMPLETED', $this->payload($client)['status']);

        self::assertSame('8.000000000000', $connection->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id = ? AND product_id = ?', [$organizationId, $productId]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id = ? AND source_type = 'SALE'", [$organizationId]));
        self::assertSame('8.000000000000', $connection->fetchOne('SELECT quantity_on_hand FROM inventory_costing.stock_valuation WHERE organization_id = ? AND product_id = ?', [$organizationId, $productId]));
        self::assertSame('3200.000000', $connection->fetchOne('SELECT total_value FROM inventory_costing.stock_valuation WHERE organization_id = ? AND product_id = ?', [$organizationId, $productId]));
        $saleValuation = $connection->fetchAssociative("SELECT type, quantity, unit_cost, value, resulting_total_value, source_reference_id, stock_movement_id FROM inventory_costing.stock_valuation_movement WHERE organization_id = ? AND type = 'SALE'", [$organizationId]);
        self::assertIsArray($saleValuation);
        self::assertSame('SALE', $saleValuation['type']);
        self::assertSame('2.000000000000', $saleValuation['quantity']);
        self::assertSame('400.000000000000', $saleValuation['unit_cost']);
        self::assertSame('800.000000', $saleValuation['value']);
        self::assertSame('3200.000000', $saleValuation['resulting_total_value']);
        self::assertSame($sale['id'], $saleValuation['source_reference_id']);
        self::assertNotNull($saleValuation['stock_movement_id']);
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ? AND type = 'SALE'", [$organizationId]));
        $costSnapshot = $connection->fetchAssociative('SELECT sale_line_id, stock_id, stock_movement_id, quantity, unit_cost, total_cost, currency, valuation_version FROM sales.sale_line_cost_snapshot WHERE organization_id = ?', [$organizationId]);
        self::assertIsArray($costSnapshot);
        self::assertSame($connection->fetchOne('SELECT id FROM sales.sale_line WHERE organization_id = ? AND sale_id = ?', [$organizationId, $sale['id']]), $costSnapshot['sale_line_id']);
        self::assertSame($connection->fetchOne('SELECT id FROM inventory.stock WHERE organization_id = ? AND product_id = ?', [$organizationId, $productId]), $costSnapshot['stock_id']);
        self::assertSame($saleValuation['stock_movement_id'], $costSnapshot['stock_movement_id']);
        self::assertSame('2.000000000000', $costSnapshot['quantity']);
        self::assertSame('400.000000000000', $costSnapshot['unit_cost']);
        self::assertSame('800.000000', $costSnapshot['total_cost']);
        self::assertSame('XAF', $costSnapshot['currency']);
        self::assertSame(2, $costSnapshot['valuation_version']);
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM sales.sale_line_cost_snapshot WHERE organization_id = ?', [$organizationId]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM cash_management.cash_movement WHERE organization_id = ? AND type = 'SALE_PAYMENT'", [$organizationId]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM payments.payment WHERE organization_id = ? AND status = 'CONFIRMED'", [$organizationId]));

        $connection->executeStatement('DELETE FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]);
        $connection->executeStatement('DELETE FROM inventory_costing.stock_valuation WHERE organization_id = ?', [$organizationId]);
        $client->request('POST', '/api/stores/' . $store['id'] . '/sales', server: $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        $unvaluedSale = $this->payload($client);
        $client->jsonRequest('POST', '/api/sales/' . $unvaluedSale['id'] . '/lines', ['productId' => $productId, 'productPackagingId' => $packagingId, 'quantity' => '1'], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        $client->jsonRequest('POST', '/api/sales/' . $unvaluedSale['id'] . '/complete', [
            'cashSessionId' => $session['id'],
            'payment' => ['method' => 'CASH', 'amount' => ['amount' => '1500', 'currency' => 'XAF']],
        ], [...$this->headers($ownerToken), 'HTTP_IDEMPOTENCY_KEY' => 'unvalued-sale-completion']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('VALUATION_NOT_INITIALIZED', $this->payload($client)['code']);
        self::assertSame('8.000000000000', $connection->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id = ? AND product_id = ?', [$organizationId, $productId]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id = ? AND source_type = 'SALE'", [$organizationId]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM cash_management.cash_movement WHERE organization_id = ? AND type = 'SALE_PAYMENT'", [$organizationId]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM payments.payment WHERE organization_id = ? AND status = 'CONFIRMED'", [$organizationId]));
        self::assertSame('DRAFT', $connection->fetchOne('SELECT status FROM sales.sale WHERE organization_id = ? AND id = ?', [$organizationId, $unvaluedSale['id']]));
    }

    private function registerOwnerAndLogin(KernelBrowser $client): string
    {
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => self::OWNER_EMAIL,
            'password' => self::PASSWORD,
            'organizationName' => 'Store scope tenant',
            'countryCode' => 'CG',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->login($client, self::OWNER_EMAIL);
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
        $client->jsonRequest('POST', '/api/stores', [
            'code' => $code,
            'name' => $name,
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'currency' => 'XAF',
            'locale' => 'fr_CG',
        ], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    private function updateStore(KernelBrowser $client, string $token, string $storeId, string $name): void
    {
        $client->jsonRequest('PATCH', '/api/stores/' . $storeId, [
            'name' => $name,
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'locale' => 'fr_CG',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ]);
    }

    /** @return array<string,mixed> */
    private function createCashRegister(KernelBrowser $client, string $token, string $storeId, string $code): array
    {
        $client->jsonRequest('POST', '/api/stores/' . $storeId . '/cash-registers', [
            'code' => $code,
            'name' => $code . ' register',
        ], $this->headers($token));
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    /** @return array<string,mixed> */
    private function openCashSession(KernelBrowser $client, string $token, string $storeId, string $registerId, string $amount): array
    {
        $client->jsonRequest('POST', '/api/stores/' . $storeId . '/cash-registers/' . $registerId . '/sessions/open', [
            'amount' => $amount,
            'currency' => 'XAF',
        ], $this->headers($token));
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    private function inviteAndRegister(KernelBrowser $client, string $ownerToken, string $email, string $roleCode, string $storeId): string
    {
        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => $email,
            'roleAssignments' => [[
                'roleCode' => $roleCode,
                'storeIds' => [$storeId],
            ]],
        ], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        $invitationToken = $this->payload($client)['token'] ?? null;
        self::assertIsString($invitationToken);

        $client->jsonRequest('POST', '/api/auth/invitations/' . $invitationToken . '/register', [
            'password' => self::PASSWORD,
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->login($client, $email);
    }

    /** @return array<string,string> */
    private function headers(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'HTTP_ACCEPT' => 'application/json'];
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
        $organizationId = $connection->fetchOne(
            'SELECT default_organization_id FROM identity_access.users WHERE email = ?',
            [self::OWNER_EMAIL],
        );
        if (is_string($organizationId)) {
            foreach ([self::OWNER_EMAIL, self::MANAGER_EMAIL, self::CASHIER_EMAIL] as $email) {
                $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [$email]);
            }
            $connection->executeStatement('DELETE FROM cash_management.cash_movement WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM cash_management.cash_session WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM cash_management.cash_register WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM payments.payment WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM sales.sale_completion_keys WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM sales.sale_line_cost_snapshot WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM sales.sale_line WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM sales.sale WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory_costing.stock_valuation WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock_movement WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM pricing.product_prices WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM pricing.price_lists WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM catalog.product_packagings WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM catalog.products WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM catalog.units_of_measure WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [$organizationId]);
        }
        foreach ([self::OWNER_EMAIL, self::MANAGER_EMAIL, self::CASHIER_EMAIL] as $email) {
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
