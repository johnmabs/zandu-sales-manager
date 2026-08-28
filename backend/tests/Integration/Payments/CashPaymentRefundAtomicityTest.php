<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Payments;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\CashManagement\Application\Contract\CashRefundRecorder;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Payments\Application\{CreateCashPaymentRefund, CreateCashPaymentRefundService};
use Zandu\Modules\Payments\Domain\{PaymentRefundRepository, PaymentRepository};
use Zandu\Modules\Sales\Application\Contract\{RefundableReturn, RefundableReturnProvider};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{ActorId, CashSessionId, OrganizationId, PaymentId, ReturnSaleId, SaleId, StoreId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\SecurityAudit\{ResourceReference, SafeAuditMetadata, SecurityAction, SecurityAuditTrail};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CashPaymentRefundAtomicityTest extends KernelTestCase
{
    private const ORGANIZATION = '019a3800-0000-7000-8000-000000000001';
    private const ACTOR = '019a3800-0000-7000-8000-000000000002';
    private const STORE = '019a3800-0000-7000-8000-000000000003';
    private const SALE = '019a3800-0000-7000-8000-000000000004';
    private const PAYMENT = '019a3800-0000-7000-8000-000000000005';
    private const RETURN = '019a3800-0000-7000-8000-000000000006';
    private const REGISTER = '019a3800-0000-7000-8000-000000000007';
    private const SESSION = '019a3800-0000-7000-8000-000000000008';

    private Connection $connection;
    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection = self::getContainer()->get(Connection::class);
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

    #[DataProvider('lateFailurePhases')]
    public function testLateFailureRollsBackRefundCashAuditAndOutbox(string $phase): void
    {
        try {
            ($this->service($phase))($this->command());
            self::fail('The injected failure should abort the refund transaction.');
        } catch (RuntimeException $exception) {
            self::assertSame('Injected ' . $phase . ' failure.', $exception->getMessage());
        }

        $this->assertNoRefundEffect();
    }

    public function testSuccessfulRefundCommitsEveryLedgerTogether(): void
    {
        $service = $this->service(null);
        $refund = $service($this->command());
        $replayed = $service($this->command());

        self::assertSame('CONFIRMED', $refund->status()->value);
        self::assertTrue($refund->id()->equals($replayed->id()));
        self::assertSame(1, $this->rowCount('payments.payment_refund'));
        self::assertSame(1, $this->rowCount('cash_management.cash_movement'));
        self::assertSame(1, $this->rowCount('security.security_audit_entries'));
        self::assertSame(2, $this->rowCount('messaging.outbox_messages'));
    }

    public function testRuntimeTenantCannotRefundAnotherTenantPayment(): void
    {
        $otherOrganization = OrganizationId::fromString('019a3800-0000-7000-8000-000000000099', $this->uuids);

        try {
            ($this->service(null))($this->command($this->actor($otherOrganization)));
            self::fail('Another tenant payment must remain hidden from the refund workflow.');
        } catch (\LogicException $exception) {
            self::assertSame('Payment not found.', $exception->getMessage());
        }

        $this->assertNoRefundEffect();
    }

    /** @return iterable<string, array{string}> */
    public static function lateFailurePhases(): iterable
    {
        yield 'audit' => ['audit'];
        yield 'outbox' => ['outbox'];
        yield 'before_commit' => ['before_commit'];
    }

    private function service(?string $failurePhase): CreateCashPaymentRefundService
    {
        $container = self::getContainer();
        $transaction = new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
        $returns = $this->createStub(RefundableReturnProvider::class);
        $returns->method('provide')->willReturn(new RefundableReturn(
            $this->organizationId(),
            $this->storeId(),
            $this->saleId(),
            $this->returnSaleId(),
            $this->money('1000'),
        ));

        return new CreateCashPaymentRefundService(
            'before_commit' === $failurePhase ? new FailingBeforeCommitTransaction($transaction) : $transaction,
            $container->get(PaymentRepository::class),
            $container->get(PaymentRefundRepository::class),
            $returns,
            $container->get(CashRefundRecorder::class),
            $this->createStub(AuthorizationService::class),
            $this->createStub(OperationalGuard::class),
            'audit' === $failurePhase ? new FailingRefundSecurityAuditTrail() : $container->get(SecurityAuditTrail::class),
            'outbox' === $failurePhase ? new FailingRefundOutboxRepository() : $container->get(OutboxRepository::class),
            new SymfonyUuidV7Generator(),
            new FrozenClock(new DateTimeImmutable('2026-08-28T12:00:00Z')),
        );
    }

    private function command(?ActorContext $actor = null): CreateCashPaymentRefund
    {
        return new CreateCashPaymentRefund(
            $this->paymentId(),
            $this->returnSaleId(),
            CashSessionId::fromString(self::SESSION, $this->uuids),
            $this->money('400'),
            'Customer return',
            IdempotencyKey::fromString('atomic-refund-key'),
            $actor ?? $this->actor(),
        );
    }

    private function assertNoRefundEffect(): void
    {
        self::assertSame(0, $this->rowCount('payments.payment_refund'));
        self::assertSame(0, $this->rowCount('cash_management.cash_movement'));
        self::assertSame(0, $this->rowCount('security.security_audit_entries'));
        self::assertSame(0, $this->rowCount('messaging.outbox_messages'));
        self::assertSame('CONFIRMED', $this->connection->fetchOne('SELECT status FROM payments.payment WHERE id = ?', [self::PAYMENT]));
        self::assertSame('OPEN', $this->connection->fetchOne('SELECT status FROM cash_management.cash_session WHERE id = ?', [self::SESSION]));
    }

    private function fixture(): void
    {
        $this->connection->executeStatement("INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)", [self::ORGANIZATION, 'Refund atomicity', self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO organization.stores (id,organization_id,code,name,status,time_zone,currency,locale,created_by,created_at,updated_by,updated_at,version) VALUES (?,?,?,?,'ACTIVE','Africa/Brazzaville','XAF','fr_CG',?,NOW(),?,NOW(),1)", [self::STORE, self::ORGANIZATION, 'ATM', 'Atomic Store', self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO cash_management.cash_register (id,organization_id,store_id,code,name,status,created_at,created_by,version) VALUES (?,?,?,?,?,'ACTIVE',NOW(),?,1)", [self::REGISTER, self::ORGANIZATION, self::STORE, 'ATM', 'Atomic Register', self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO cash_management.cash_session (id,organization_id,store_id,cash_register_id,cashier_id,currency,opening_balance,opened_at,status,version) VALUES (?,?,?,?,?,'XAF',1000,NOW(),'OPEN',1)", [self::SESSION, self::ORGANIZATION, self::STORE, self::REGISTER, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.sale (id,organization_id,store_id,status,currency,subtotal,discount_total,tax_total,total,created_by,created_at,completed_by,completed_at,business_date,version) VALUES (?,?,?,'COMPLETED','XAF',1000,0,0,1000,?,NOW(),?,NOW(),CURRENT_DATE,2)", [self::SALE, self::ORGANIZATION, self::STORE, self::ACTOR, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO payments.payment (id,organization_id,purpose,target_reference,method,status,amount,currency,created_by,created_at,confirmed_at,version) VALUES (?,?,'SALE',?,'CASH','CONFIRMED',1000,'XAF',?,NOW(),NOW(),2)", [self::PAYMENT, self::ORGANIZATION, self::SALE, self::ACTOR]);
        $this->connection->executeStatement("INSERT INTO sales.return_sale (id,organization_id,store_id,sale_id,status,created_by,created_at,business_date,completed_by,completed_at,version) VALUES (?,?,?,?,'COMPLETED',?,NOW(),CURRENT_DATE,?,NOW(),2)", [self::RETURN, self::ORGANIZATION, self::STORE, self::SALE, self::ACTOR, self::ACTOR]);
    }

    private function cleanup(): void
    {
        $this->connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [self::ORGANIZATION]);
        $this->connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [self::ORGANIZATION]);
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

    private function rowCount(string $table): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM ' . $table . ' WHERE organization_id = ?', [self::ORGANIZATION]);
    }

    private function actor(?OrganizationId $organizationId = null): ActorContext
    {
        return new ActorContext(
            ActorId::fromString(self::ACTOR, $this->uuids),
            $organizationId ?? $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString('019a3800-0000-7000-8000-000000000010', $this->uuids),
            new DateTimeImmutable('2026-08-28T11:00:00Z'),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION, $this->uuids);
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString(self::STORE, $this->uuids);
    }

    private function saleId(): SaleId
    {
        return SaleId::fromString(self::SALE, $this->uuids);
    }

    private function paymentId(): PaymentId
    {
        return PaymentId::fromString(self::PAYMENT, $this->uuids);
    }

    private function returnSaleId(): ReturnSaleId
    {
        return ReturnSaleId::fromString(self::RETURN, $this->uuids);
    }

    private function money(string $amount): Money
    {
        return Money::fromString($amount, Currency::fromCode('XAF'), $this->decimals);
    }
}

final readonly class FailingBeforeCommitTransaction implements TenantTransaction
{
    public function __construct(private TenantTransaction $transaction) {}

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $this->transaction->transactional($organizationId, function () use ($operation): never {
            $operation();

            throw new RuntimeException('Injected before_commit failure.');
        });
    }
}

final class FailingRefundSecurityAuditTrail implements SecurityAuditTrail
{
    public function recordSuccess(ActorContext $actor, SecurityAction $action, ResourceReference $resource, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt, ?OrganizationId $organizationId = null): never
    {
        throw new RuntimeException('Injected audit failure.');
    }

    public function recordDenied(ActorContext $actor, ResourceReference $resource, string $reason, SafeAuditMetadata $metadata, DateTimeImmutable $occurredAt): never
    {
        throw new RuntimeException('Injected audit failure.');
    }
}

final class FailingRefundOutboxRepository implements OutboxRepository
{
    public function append(OutboxMessage $message): never
    {
        throw new RuntimeException('Injected outbox failure.');
    }
}
