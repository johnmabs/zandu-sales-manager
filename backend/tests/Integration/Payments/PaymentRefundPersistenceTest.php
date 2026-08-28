<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Payments;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\Payments\Domain\{PaymentRefund, PaymentRefundRepository};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{ActorId, CashSessionId, OrganizationId, PaymentId, PaymentRefundId, ReturnSaleId};
use Zandu\SharedKernel\Money\{Currency, Money};

final class PaymentRefundPersistenceTest extends KernelTestCase
{
    private const string ORGANIZATION = '019a3700-0000-7000-8000-000000000001';
    private const string OTHER_ORGANIZATION = '019a3700-0000-7000-8000-000000000099';
    private const string ACTOR = '019a3700-0000-7000-8000-000000000002';
    private const string STORE = '019a3700-0000-7000-8000-000000000003';
    private const string SALE = '019a3700-0000-7000-8000-000000000004';
    private const string PAYMENT = '019a3700-0000-7000-8000-000000000005';
    private const string RETURN = '019a3700-0000-7000-8000-000000000006';
    private const string REGISTER = '019a3700-0000-7000-8000-000000000007';
    private const string SESSION = '019a3700-0000-7000-8000-000000000008';
    private const string REFUND = '019a3700-0000-7000-8000-000000000009';

    private Connection $connection;
    private DoctrineTenantTransaction $transaction;
    private PaymentRefundRepository $refunds;
    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->connection = $container->get(Connection::class);
        $this->transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $this->refunds = $container->get(PaymentRefundRepository::class);
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->cleanup();
        $this->fixture();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testConfirmedRefundRoundTripsAndIsTenantIsolatedAppendOnly(): void
    {
        $organization = $this->organization(self::ORGANIZATION);
        $refund = PaymentRefund::create(
            PaymentRefundId::fromString(self::REFUND, $this->uuids),
            $organization,
            PaymentId::fromString(self::PAYMENT, $this->uuids),
            ReturnSaleId::fromString(self::RETURN, $this->uuids),
            CashSessionId::fromString(self::SESSION, $this->uuids),
            Money::fromString('400', Currency::fromCode('XAF'), $this->decimals),
            'Customer return',
            IdempotencyKey::fromString('refund-key'),
            hash('sha256', 'payload'),
            ActorId::fromString(self::ACTOR, $this->uuids),
            new DateTimeImmutable('2026-08-28T12:00:00Z'),
        );
        $refund->confirm(new DateTimeImmutable('2026-08-28T12:00:00Z'));
        $this->transaction->transactional($organization, fn() => $this->refunds->add($refund));

        $restored = $this->transaction->transactional($organization, fn() => $this->refunds->findByIdempotencyKey($organization, $refund->paymentId(), $refund->idempotencyKey()));
        self::assertNotNull($restored);
        self::assertSame('CONFIRMED', $restored->status()->value);
        self::assertSame('400.000000000000', $this->transaction->transactional($organization, fn() => $this->refunds->confirmedTotalForReturn($organization, $refund->returnSaleId(), Currency::fromCode('XAF')))->amount()->toString());
        self::assertFalse((bool) $this->connection->fetchOne("SELECT has_table_privilege('zandu_runtime', 'payments.payment_refund', 'UPDATE')"));

        $other = $this->organization(self::OTHER_ORGANIZATION);
        self::assertNull($this->transaction->transactional($other, fn() => $this->refunds->findByIdempotencyKey($organization, $refund->paymentId(), $refund->idempotencyKey())));
    }

    private function fixture(): void
    {
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, 'Refunds', self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,?,'ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, 'REF', 'Refund Store', self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO cash_management.cash_register (id,organization_id,store_id,code,name,status,created_at,created_by,version) VALUES (?,?,?,?,?,'ACTIVE',NOW(),?,1)", [self::REGISTER, self::ORGANIZATION, self::STORE, 'REG', 'Register', self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO cash_management.cash_session (id,organization_id,store_id,cash_register_id,cashier_id,currency,opening_balance,opened_at,status,version) VALUES (?,?,?,?,?,'XAF',1000,NOW(),'OPEN',1)", [self::SESSION, self::ORGANIZATION, self::STORE, self::REGISTER, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale (id,organization_id,store_id,status,currency,subtotal,discount_total,tax_total,total,created_by,created_at,completed_by,completed_at,business_date,version) VALUES (?,?,?,'COMPLETED','XAF',1000,0,0,1000,?,NOW(),?,NOW(),CURRENT_DATE,2)", [self::SALE, self::ORGANIZATION, self::STORE, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO payments.payment (id,organization_id,purpose,target_reference,method,status,amount,currency,created_by,created_at,confirmed_at,version) VALUES (?,?,'SALE',?,'CASH','CONFIRMED',1000,'XAF',?,NOW(),NOW(),2)", [self::PAYMENT, self::ORGANIZATION, self::SALE, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.return_sale (id,organization_id,store_id,sale_id,status,created_by,created_at,business_date,completed_by,completed_at,version) VALUES (?,?,?,?,'COMPLETED',?,NOW(),CURRENT_DATE,?,NOW(),2)", [self::RETURN, self::ORGANIZATION, self::STORE, self::SALE, self::ACTOR, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $this->connection->executeStatement('DELETE FROM payments.payment_refund WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM cash_management.cash_movement WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM sales.return_sale WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM payments.payment WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM sales.sale WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM cash_management.cash_session WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM cash_management.cash_register WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION]);
    }

    private function organization(string $id): OrganizationId
    {
        return OrganizationId::fromString($id, $this->uuids);
    }
}
