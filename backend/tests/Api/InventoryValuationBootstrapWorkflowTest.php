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
    private function createStore(KernelBrowser $client, string $token): array
    {
        $client->jsonRequest('POST', '/api/stores', [
            'code' => 'COSTING',
            'name' => 'Costing Store',
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
