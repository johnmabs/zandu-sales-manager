<?php

declare(strict_types=1);

namespace Zandu\Tests\Api;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\{PreserveGlobalState, RunInSeparateProcess};
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InventoryValuationBootstrapWorkflowTest extends WebTestCase
{
    private const OWNER_EMAIL = 'costing-owner@example.com';
    private const MANAGER_EMAIL = 'costing-manager@example.com';
    private const PASSWORD = 'a-strong-password-for-zandu';
    private const UNIT = '0198f501-1111-7111-8111-111111111111';
    private const PRODUCT = '0198f502-1111-7111-8111-111111111111';
    private const PACKAGING = '0198f503-1111-7111-8111-111111111111';
    private const STOCK = '0198f504-1111-7111-8111-111111111111';

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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOwnerBootstrapsValuationAtomicallyWhileManagerIsDenied(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerOwnerAndLogin($client);
        $store = $this->createStore($client, $ownerToken);
        [$organizationId, $actorId] = $this->ownerIdentity();
        $this->catalogFixture($organizationId, $actorId);

        $this->historicalStockFixture($organizationId, $store['id'], $actorId);

        $managerToken = $this->inviteManager($client, $ownerToken, $store['id']);
        $uri = sprintf('/api/stores/%s/inventory-valuations/%s/initialize', $store['id'], self::PRODUCT);
        $payload = ['openingUnitCost' => '4000.1234567890123', 'reason' => 'Controlled opening count'];

        $client->jsonRequest('POST', $uri, $payload, $this->headers($managerToken));
        self::assertResponseStatusCodeSame(403);
        self::assertSame('FORBIDDEN', $this->payload($client)['code']);

        $client->jsonRequest('POST', $uri, $payload, $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        $valuation = $this->payload($client);
        self::assertSame($organizationId, $valuation['organizationId']);
        self::assertSame($store['id'], $valuation['storeId']);
        self::assertSame(self::PRODUCT, $valuation['productId']);
        self::assertSame('10.000000000000', $valuation['quantityOnHand']);
        self::assertSame('40001.234568', $valuation['totalValue']);
        self::assertSame('4000.123456800000', $valuation['averageUnitCost']);
        self::assertSame('XAF', $valuation['currency']);
        self::assertSame(1, $valuation['version']);

        $connection = $this->entityManager->getConnection();
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation WHERE organization_id = ?', [$organizationId]));
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]));
        self::assertSame('Controlled opening count', $connection->fetchOne('SELECT source_reference_id FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]));
        self::assertSame('STOCK_VALUATION_INITIALIZED', $connection->fetchOne('SELECT action FROM security.security_audit_entries WHERE organization_id = ? AND action = ?', [$organizationId, 'STOCK_VALUATION_INITIALIZED']));
        self::assertSame('inventory_costing.stock_valuation_initialized.v1', $connection->fetchOne('SELECT type FROM messaging.outbox_messages WHERE organization_id = ? AND type = ?', [$organizationId, 'inventory_costing.stock_valuation_initialized.v1']));

        $client->jsonRequest('POST', $uri, $payload, $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('VALUATION_ALREADY_INITIALIZED', $this->payload($client)['code']);
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation WHERE organization_id = ?', [$organizationId]));
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOperationalInventoryMovementsAreValuedAtomically(): void
    {
        $client = self::createClient();
        $ownerToken = $this->registerOwnerAndLogin($client);
        $store = $this->createStore($client, $ownerToken);
        [$organizationId, $actorId] = $this->ownerIdentity();
        $this->catalogFixture($organizationId, $actorId);
        $stockUri = sprintf('/api/stores/%s/stocks/%s', $store['id'], self::PRODUCT);

        $client->jsonRequest('POST', $stockUri . '/initialize', [
            'quantity' => '10',
            'unitCost' => '4000',
        ], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        self::assertSame('10', $this->payload($client)['quantityOnHand']);
        $this->assertValuation($organizationId, '10.000000000000', '40000.000000', '4000.000000000000', 1, 'INITIAL_STOCK');

        $client->jsonRequest('POST', $stockUri . '/adjust', [
            'delta' => '10',
            'reason' => 'Restock',
            'unitCost' => '6000',
        ], $this->headers($ownerToken));
        self::assertResponseIsSuccessful();
        self::assertSame('20.000000000000', $this->payload($client)['quantityOnHand']);
        $this->assertValuation($organizationId, '20.000000000000', '100000.000000', '5000.000000000000', 2, 'ADJUSTMENT_IN');

        $client->jsonRequest('POST', $stockUri . '/adjust', [
            'delta' => '-5',
            'reason' => 'Shrinkage',
        ], $this->headers($ownerToken));
        self::assertResponseIsSuccessful();
        self::assertSame('15.000000000000', $this->payload($client)['quantityOnHand']);
        $this->assertValuation($organizationId, '15.000000000000', '75000.000000', '5000.000000000000', 3, 'ADJUSTMENT_OUT');

        $connection = $this->entityManager->getConnection();
        $movementCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id = ?', [$organizationId]);

        $client->jsonRequest('POST', $stockUri . '/adjust', [
            'delta' => '1',
            'reason' => 'Missing cost',
        ], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('VALUATION_UNIT_COST_REQUIRED', $this->payload($client)['code']);

        $client->jsonRequest('POST', $stockUri . '/adjust', [
            'delta' => '-1',
            'reason' => 'Unexpected cost',
            'unitCost' => '5000',
        ], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('VALUATION_UNIT_COST_UNEXPECTED', $this->payload($client)['code']);

        self::assertSame('15.000000000000', $connection->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id = ?', [$organizationId]));
        self::assertSame($movementCount, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id = ?', [$organizationId]));
        self::assertSame(3, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStockTransferApiCoversDraftShipmentReceptionReplayAndCancellation(): void
    {
        $client = self::createClient();
        $token = $this->registerOwnerAndLogin($client);
        $source = $this->createStore($client, $token, 'TRANSFER-SOURCE', 'Transfer source');
        $destination = $this->createStore($client, $token, 'TRANSFER-DEST', 'Transfer destination');
        [$organizationId, $actorId] = $this->ownerIdentity();
        $this->catalogFixture($organizationId, $actorId);

        $client->jsonRequest('POST', '/api/stock-transfers', ['sourceStoreId' => $source['id'], 'destinationStoreId' => $source['id']], $this->headers($token));
        self::assertResponseStatusCodeSame(422);
        self::assertSame('STOCK_TRANSFER_SAME_STORE', $this->payload($client)['code']);
        $client->request('GET', '/api/stock-transfers/019a0400-0000-7000-8000-000000000001', server: $this->headers($token));
        self::assertResponseStatusCodeSame(404);
        self::assertSame('NOT_FOUND', $this->payload($client)['code']);

        $client->jsonRequest('POST', sprintf('/api/stores/%s/stocks/%s/initialize', $source['id'], self::PRODUCT), ['quantity' => '10', 'unitCost' => '400'], $this->headers($token));
        self::assertResponseStatusCodeSame(201);

        $client->jsonRequest('POST', '/api/stock-transfers', ['sourceStoreId' => $source['id'], 'destinationStoreId' => $destination['id']], $this->headers($token));
        self::assertResponseStatusCodeSame(201);
        $transfer = $this->payload($client);
        self::assertSame('DRAFT', $transfer['status']);

        $client->jsonRequest('POST', '/api/stock-transfers/' . $transfer['id'] . '/lines', ['productId' => self::PRODUCT, 'requestedQuantity' => '5'], $this->headers($token));
        self::assertResponseStatusCodeSame(201);
        $transfer = $this->payload($client);
        $line = $transfer['lines'][0];
        self::assertIsArray($line);

        $client->jsonRequest('PATCH', sprintf('/api/stock-transfers/%s/lines/%s', $transfer['id'], $line['id']), ['requestedQuantity' => '4', 'expectedVersion' => $transfer['version']], [...$this->headers($token), 'CONTENT_TYPE' => 'application/merge-patch+json']);
        self::assertResponseIsSuccessful();
        self::assertSame('4', $this->payload($client)['lines'][0]['requestedQuantity']);

        $ship = ['lines' => [['lineId' => $line['id'], 'shippedQuantity' => '3']]];
        $client->jsonRequest('POST', '/api/stock-transfers/' . $transfer['id'] . '/ship', ['lines' => [['lineId' => $line['id'], 'shippedQuantity' => '5']]], [...$this->headers($token), 'HTTP_IDEMPOTENCY_KEY' => 'api-transfer-invalid-ship']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('TRANSFER_SHIPPED_QUANTITY_EXCEEDS_REQUESTED', $this->payload($client)['code']);
        $client->jsonRequest('POST', '/api/stock-transfers/' . $transfer['id'] . '/ship', $ship, [...$this->headers($token), 'HTTP_IDEMPOTENCY_KEY' => 'api-transfer-ship']);
        self::assertResponseIsSuccessful();
        self::assertSame('SHIPPED', $this->payload($client)['status']);
        $client->jsonRequest('POST', '/api/stock-transfers/' . $transfer['id'] . '/ship', $ship, [...$this->headers($token), 'HTTP_IDEMPOTENCY_KEY' => 'api-transfer-ship']);
        self::assertResponseIsSuccessful();
        self::assertSame('SHIPPED', $this->payload($client)['status']);

        $receive = ['lines' => [['lineId' => $line['id'], 'receivedQuantity' => '2']]];
        $client->jsonRequest('POST', '/api/stock-transfers/' . $transfer['id'] . '/receive', ['lines' => [['lineId' => $line['id'], 'receivedQuantity' => '4']]], [...$this->headers($token), 'HTTP_IDEMPOTENCY_KEY' => 'api-transfer-invalid-receive']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('TRANSFER_RECEIVED_QUANTITY_EXCEEDS_SHIPPED', $this->payload($client)['code']);
        $client->jsonRequest('POST', '/api/stock-transfers/' . $transfer['id'] . '/receive', $receive, [...$this->headers($token), 'HTTP_IDEMPOTENCY_KEY' => 'api-transfer-receive']);
        self::assertResponseIsSuccessful();
        $received = $this->payload($client);
        self::assertSame('RECEIVED', $received['status']);
        self::assertTrue($received['hasTransitDiscrepancy']);
        self::assertSame('1.000000000000', $received['lines'][0]['transitDiscrepancy']);
        self::assertArrayNotHasKey('shippedUnitCostSnapshot', $received['lines'][0], 'The operational transfer API must not leak inventory costs.');

        $client->request('GET', '/api/stock-transfers/' . $transfer['id'], server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('RECEIVED', $this->payload($client)['status']);
        $client->request('GET', '/api/stock-transfers', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->payload($client));

        $client->jsonRequest('POST', '/api/stock-transfers', ['sourceStoreId' => $source['id'], 'destinationStoreId' => $destination['id']], $this->headers($token));
        self::assertResponseStatusCodeSame(201);
        $cancelledTransfer = $this->payload($client);
        $client->jsonRequest('POST', '/api/stock-transfers/' . $cancelledTransfer['id'] . '/lines', ['productId' => self::PRODUCT, 'requestedQuantity' => '1'], $this->headers($token));
        self::assertResponseStatusCodeSame(201);
        $removableLineId = $this->payload($client)['lines'][0]['id'];
        $client->request('DELETE', sprintf('/api/stock-transfers/%s/lines/%s', $cancelledTransfer['id'], $removableLineId), server: $this->headers($token));
        self::assertResponseStatusCodeSame(204);
        $client->jsonRequest('POST', '/api/stock-transfers/' . $cancelledTransfer['id'] . '/cancel', ['reason' => 'API cancellation test'], $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('CANCELLED', $this->payload($client)['status']);

        $connection = $this->entityManager->getConnection();
        self::assertSame('7.000000000000', $connection->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND store_id=? AND product_id=?', [$organizationId, $source['id'], self::PRODUCT]));
        self::assertSame('2.000000000000', $connection->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND store_id=? AND product_id=?', [$organizationId, $destination['id'], self::PRODUCT]));
        self::assertSame(2, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND source_type=?', [$organizationId, 'TRANSFER']));
        self::assertSame(2, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id=? AND source_type=?', [$organizationId, 'TRANSFER']));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStockCountApiCoversBlindRecordingBatchFinalizationReplayAndCancellation(): void
    {
        $client = self::createClient();
        $token = $this->registerOwnerAndLogin($client);
        $store = $this->createStore($client, $token, 'COUNT-STORE', 'Count store');
        [$organizationId, $actorId] = $this->ownerIdentity();
        $this->catalogFixture($organizationId, $actorId);

        $client->jsonRequest('POST', sprintf('/api/stores/%s/stocks/%s/initialize', $store['id'], self::PRODUCT), ['quantity' => '10', 'unitCost' => '400'], $this->headers($token));
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/stock-counts/019a0400-0000-7000-8000-000000000001', server: $this->headers($token));
        self::assertResponseStatusCodeSame(404);
        self::assertSame('NOT_FOUND', $this->payload($client)['code']);

        $client->jsonRequest('POST', '/api/stores/' . $store['id'] . '/stock-counts', [
            'scopeType' => 'PARTIAL',
            'mode' => 'BLIND',
            'productIds' => [self::PRODUCT],
        ], $this->headers($token));
        self::assertResponseStatusCodeSame(201);
        $count = $this->payload($client);
        self::assertSame('DRAFT', $count['status']);

        $client->request('POST', '/api/stock-counts/' . $count['id'] . '/start', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        $started = $this->payload($client);
        self::assertSame('OPEN', $started['status']);
        self::assertSame(1, $started['totalLineCount']);
        self::assertArrayNotHasKey('expectedQuantity', $started['lines'][0]);
        self::assertArrayNotHasKey('variance', $started['lines'][0]);

        $client->jsonRequest('POST', '/api/stock-counts/' . $count['id'] . '/counts', [
            'productId' => self::PRODUCT,
            'countedQuantity' => '9',
            'expectedLineVersion' => 1,
        ], $this->headers($token));
        self::assertResponseIsSuccessful();
        $recorded = $this->payload($client);
        self::assertSame('9.000000000000', $recorded['lines'][0]['countedQuantity']);
        self::assertArrayNotHasKey('expectedQuantity', $recorded['lines'][0]);

        $client->jsonRequest('POST', '/api/stock-counts/' . $count['id'] . '/counts/batch', [
            'entries' => [[
                'productId' => self::PRODUCT,
                'countedQuantity' => '8',
                'expectedLineVersion' => 2,
            ]],
        ], $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('8.000000000000', $this->payload($client)['lines'][0]['countedQuantity']);

        $client->jsonRequest('POST', '/api/stock-counts/' . $count['id'] . '/finalization', ['batchSize' => 1], $this->headers($token));
        self::assertResponseIsSuccessful();
        $completed = $this->payload($client);
        self::assertSame('COMPLETED', $completed['status']);
        self::assertSame('10.000000000000', $completed['lines'][0]['expectedQuantity']);
        self::assertSame('-2.000000000000', $completed['lines'][0]['variance']);
        self::assertSame(1, $completed['reconciledLineCount']);

        $client->jsonRequest('POST', '/api/stock-counts/' . $count['id'] . '/finalization', ['batchSize' => 1], $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('COMPLETED', $this->payload($client)['status']);

        $client->request('GET', '/api/stock-counts', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->payload($client));

        $client->jsonRequest('POST', '/api/stores/' . $store['id'] . '/stock-counts', [
            'scopeType' => 'PARTIAL',
            'mode' => 'GUIDED',
            'productIds' => [self::PRODUCT],
        ], $this->headers($token));
        self::assertResponseStatusCodeSame(201);
        $guided = $this->payload($client);
        $client->request('POST', '/api/stock-counts/' . $guided['id'] . '/start', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('8.000000000000', $this->payload($client)['lines'][0]['expectedQuantity']);
        $client->request('POST', '/api/stock-counts/' . $guided['id'] . '/cancel', server: $this->headers($token));
        self::assertResponseIsSuccessful();
        self::assertSame('CANCELLED', $this->payload($client)['status']);

        $connection = $this->entityManager->getConnection();
        self::assertSame('8.000000000000', $connection->fetchOne('SELECT quantity_on_hand FROM inventory.stock WHERE organization_id=? AND store_id=? AND product_id=?', [$organizationId, $store['id'], self::PRODUCT]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM inventory.stock_movement WHERE organization_id=? AND source_type='STOCK_COUNT'", [$organizationId]));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id=? AND source_type='STOCK_COUNT'", [$organizationId]));
        self::assertSame(0, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory.open_stock_count_scope WHERE organization_id=?', [$organizationId]));
    }

    private function registerOwnerAndLogin(KernelBrowser $client): string
    {
        $client->jsonRequest('POST', '/api/auth/register', [
            'email' => self::OWNER_EMAIL,
            'password' => self::PASSWORD,
            'organizationName' => 'Costing tenant',
            'countryCode' => 'CG',
            'defaultCurrency' => 'XAF',
            'defaultTimeZone' => 'Africa/Brazzaville',
            'defaultLocale' => 'fr_CG',
        ]);
        self::assertResponseStatusCodeSame(201);

        return $this->login($client, self::OWNER_EMAIL);
    }

    /** @return array<string, mixed> */
    private function createStore(KernelBrowser $client, string $token, string $code = 'COSTING', string $name = 'Costing Store'): array
    {
        $client->jsonRequest('POST', '/api/stores', [
            'code' => $code,
            'name' => $name,
            'address' => null,
            'timeZone' => 'Africa/Brazzaville',
            'currency' => 'XAF',
            'locale' => 'fr_CG',
        ], $this->headers($token));
        self::assertResponseStatusCodeSame(201);

        return $this->payload($client);
    }

    private function inviteManager(KernelBrowser $client, string $ownerToken, string $storeId): string
    {
        $client->jsonRequest('POST', '/api/member-invitations', [
            'email' => self::MANAGER_EMAIL,
            'roleAssignments' => [[
                'roleCode' => 'STORE_MANAGER',
                'storeIds' => [$storeId],
            ]],
        ], $this->headers($ownerToken));
        self::assertResponseStatusCodeSame(201);
        $invitationToken = $this->payload($client)['token'] ?? null;
        self::assertIsString($invitationToken);

        $client->jsonRequest('POST', '/api/auth/invitations/' . $invitationToken . '/register', ['password' => self::PASSWORD]);
        self::assertResponseStatusCodeSame(201);

        return $this->login($client, self::MANAGER_EMAIL);
    }

    private function login(KernelBrowser $client, string $email): string
    {
        $client->jsonRequest('POST', '/api/auth/login', ['email' => $email, 'password' => self::PASSWORD]);
        self::assertResponseIsSuccessful();
        $token = $this->payload($client)['token'] ?? null;
        self::assertIsString($token);

        return $token;
    }

    /** @return array{string, string} */
    private function ownerIdentity(): array
    {
        $connection = $this->entityManager->getConnection();
        $row = $connection->fetchAssociative('SELECT default_organization_id, actor_id FROM identity_access.users WHERE email = ?', [self::OWNER_EMAIL]);
        self::assertIsArray($row);
        self::assertIsString($row['default_organization_id']);
        self::assertIsString($row['actor_id']);

        return [$row['default_organization_id'], $row['actor_id']];
    }

    private function catalogFixture(string $organizationId, string $actorId): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement("INSERT INTO catalog.units_of_measure (id,organization_id,code,name,dimension,precision,rounding_mode,status,version) VALUES (?,?,'EA','Article','COUNT',12,'HalfUp','ACTIVE',1)", [self::UNIT, $organizationId]);
        $connection->executeStatement("INSERT INTO catalog.products (id,organization_id,product_code,name,status,type,base_unit_id,inventory_tracked,created_at,created_by,activated_at,activated_by,version) VALUES (?,?,'COST-SKU','Costed product','ACTIVE','PHYSICAL',?,TRUE,NOW(),?,NOW(),?,1)", [self::PRODUCT, $organizationId, self::UNIT, $actorId, $actorId]);
        $connection->executeStatement("INSERT INTO catalog.product_packagings (id,organization_id,product_id,base,code,name,unit_id,conversion_factor,precision,minimum_quantity,quantity_increment,allowed_for_sale,allowed_for_purchase,status,created_at,created_by,version) VALUES (?,?,?,TRUE,'EA','Article',?,1,12,0.000000000001,0.000000000001,TRUE,TRUE,'ACTIVE',date_trunc('second',NOW()),?,1)", [self::PACKAGING, $organizationId, self::PRODUCT, self::UNIT, $actorId]);
    }

    private function historicalStockFixture(string $organizationId, string $storeId, string $actorId): void
    {
        $this->entityManager->getConnection()->executeStatement(
            "INSERT INTO inventory.stock (id,organization_id,store_id,product_id,quantity_on_hand,initialized,initialized_at,initialized_by,version) VALUES (?,?,?,?,10,TRUE,date_trunc('second',NOW()),?,1)",
            [self::STOCK, $organizationId, $storeId, self::PRODUCT, $actorId],
        );
    }

    private function assertValuation(
        string $organizationId,
        string $quantity,
        string $totalValue,
        string $averageUnitCost,
        int $movementCount,
        string $lastMovementType,
    ): void {
        $connection = $this->entityManager->getConnection();
        $valuation = $connection->fetchAssociative(
            'SELECT quantity_on_hand, total_value FROM inventory_costing.stock_valuation WHERE organization_id = ?',
            [$organizationId],
        );
        self::assertIsArray($valuation);
        self::assertSame($quantity, $valuation['quantity_on_hand']);
        self::assertSame($totalValue, $valuation['total_value']);
        self::assertSame($movementCount, (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]));
        self::assertSame(
            $lastMovementType,
            $connection->fetchOne('SELECT type FROM inventory_costing.stock_valuation_movement WHERE organization_id = ? ORDER BY occurred_at DESC, id DESC LIMIT 1', [$organizationId]),
        );
        self::assertSame(
            $averageUnitCost,
            $connection->fetchOne('SELECT resulting_average_cost FROM inventory_costing.stock_valuation_movement WHERE organization_id = ? ORDER BY occurred_at DESC, id DESC LIMIT 1', [$organizationId]),
        );
        self::assertSame(
            0,
            (int) $connection->fetchOne('SELECT COUNT(*) FROM inventory_costing.stock_valuation_movement WHERE organization_id = ? AND stock_movement_id IS NULL', [$organizationId]),
        );
    }

    /** @return array<string, string> */
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
        $organizationId = $connection->fetchOne('SELECT default_organization_id FROM identity_access.users WHERE email = ?', [self::OWNER_EMAIL]);
        if (is_string($organizationId)) {
            $connection->executeStatement('DELETE FROM inventory_costing.stock_valuation_movement WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory_costing.stock_valuation WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock_transfer_command WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock_transfer_line WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock_transfer WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.open_stock_count_scope WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock_count_line WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock_count WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock_movement WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM inventory.stock WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM catalog.product_packagings WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM catalog.products WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM catalog.units_of_measure WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [$organizationId]);
            $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [$organizationId]);
        }
        foreach ([self::OWNER_EMAIL, self::MANAGER_EMAIL] as $email) {
            $connection->executeStatement('DELETE FROM identity_access.refresh_session WHERE user_identifier = ?', [$email]);
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
