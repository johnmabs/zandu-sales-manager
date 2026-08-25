<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\SecurityAudit;

use DateTimeImmutable;
use RuntimeException;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Identity\SymfonyUuidV7Generator;
use Zandu\Platform\Persistence\DbalOutboxRepository;
use Zandu\Platform\Persistence\DbalSecurityAuditRepository;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\Platform\SecurityAudit\TransactionalSecurityAuditTrail;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Messaging\OutboxMessage;
use Zandu\SharedKernel\Messaging\OutboxRepository;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditEntry;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditRepository;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\Tests\Integration\PostgresTestCase;

final class TransactionalAuditAtomicityTest extends PostgresTestCase
{
    private const ORGANIZATION_ID = '0198e481-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198e482-147c-72d5-b75a-a936797ff9c8';
    private const CORRELATION_ID = '0198e483-147c-72d5-b75a-a936797ff9c8';

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanup();
        $this->connection->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (
    id, name, status, country_code, default_currency, default_time_zone, default_locale,
    created_by, created_at, updated_by, updated_at, version
) VALUES (?, 'Before audit', 'ACTIVE', 'CG', 'XAF', 'Africa/Brazzaville', 'fr_CG', ?, NOW(), ?, NOW(), 1)
SQL, [self::ORGANIZATION_ID, self::ACTOR_ID, self::ACTOR_ID]);
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testBusinessMutationAuditAndOutboxCommitTogether(): void
    {
        $this->transaction()->transactional($this->organizationId(), function (): void {
            $this->connection->executeStatement(
                'UPDATE organization.organizations SET name = ? WHERE id = ?',
                ['After audit', self::ORGANIZATION_ID],
            );
            $this->trail()->recordSuccess(
                $this->context(),
                SecurityAction::OrganizationUpdated,
                ResourceReference::for('organization', $this->organizationId()),
                SafeAuditMetadata::empty(),
                new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
            );
        });

        self::assertSame('After audit', $this->organizationName());
        self::assertSame(1, $this->auditCount());
        self::assertSame(1, $this->outboxCount());
    }

    public function testFailureBeforeAuditPersistenceRollsBackAdministrativeMutation(): void
    {
        $this->executeFailingUpdate($this->trail(new FailingSecurityAuditRepository()));

        $this->assertNoPartialEffect();
    }

    public function testFailureBeforeOutboxPersistenceRollsBackMutationAndAudit(): void
    {
        $this->executeFailingUpdate($this->trail(outbox: new FailingOutboxRepository()));

        $this->assertNoPartialEffect();
    }

    public function testFailureBeforeCommitRollsBackMutationAuditAndOutbox(): void
    {
        $failureCaught = false;
        try {
            $this->transaction()->transactional($this->organizationId(), function (): never {
                $this->connection->executeStatement(
                    'UPDATE organization.organizations SET name = ? WHERE id = ?',
                    ['Must rollback', self::ORGANIZATION_ID],
                );
                $this->trail()->recordSuccess(
                    $this->context(),
                    SecurityAction::OrganizationUpdated,
                    ResourceReference::for('organization', $this->organizationId()),
                    SafeAuditMetadata::empty(),
                    new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
                );

                throw new RuntimeException('Injected failure.');
            });
        } catch (RuntimeException) {
            $failureCaught = true;
        }

        self::assertTrue($failureCaught);
        $this->assertNoPartialEffect();
    }

    private function transaction(): DoctrineTenantTransaction
    {
        return new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
    }

    private function trail(
        ?SecurityAuditRepository $audits = null,
        ?OutboxRepository $outbox = null,
    ): TransactionalSecurityAuditTrail {
        return new TransactionalSecurityAuditTrail(
            $audits ?? new DbalSecurityAuditRepository($this->connection),
            $outbox ?? new DbalOutboxRepository($this->connection),
            new SymfonyUuidV7Generator(),
        );
    }

    private function executeFailingUpdate(SecurityAuditTrail $trail): void
    {
        $failureCaught = false;
        try {
            $this->transaction()->transactional($this->organizationId(), function () use ($trail): void {
                $this->connection->executeStatement(
                    'UPDATE organization.organizations SET name = ? WHERE id = ?',
                    ['Must rollback', self::ORGANIZATION_ID],
                );
                $trail->recordSuccess(
                    $this->context(),
                    SecurityAction::OrganizationUpdated,
                    ResourceReference::for('organization', $this->organizationId()),
                    SafeAuditMetadata::empty(),
                    new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
                );
            });
        } catch (RuntimeException) {
            $failureCaught = true;
        }

        self::assertTrue($failureCaught);
    }

    private function assertNoPartialEffect(): void
    {
        self::assertSame('Before audit', $this->organizationName());
        self::assertSame(0, $this->auditCount());
        self::assertSame(0, $this->outboxCount());
    }

    private function context(): ActorContext
    {
        $factory = new SymfonyUuidFactory();

        return new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            new DateTimeImmutable('2026-08-24T09:00:00+00:00'),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory());
    }

    private function organizationName(): string
    {
        return (string) $this->connection->fetchOne('SELECT name FROM organization.organizations WHERE id = ?', [self::ORGANIZATION_ID]);
    }

    private function auditCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM security.security_audit_entries WHERE organization_id = ?', [self::ORGANIZATION_ID]);
    }

    private function outboxCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messaging.outbox_messages WHERE organization_id = ?', [self::ORGANIZATION_ID]);
    }

    private function cleanup(): void
    {
        $this->connection->executeStatement('DELETE FROM messaging.outbox_messages WHERE organization_id = ?', [self::ORGANIZATION_ID]);
        $this->connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [self::ORGANIZATION_ID]);
        $this->connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION_ID]);
    }
}

final class FailingSecurityAuditRepository implements SecurityAuditRepository
{
    public function append(SecurityAuditEntry $entry): never
    {
        throw new RuntimeException('Injected failure before audit persistence.');
    }
}

final class FailingOutboxRepository implements OutboxRepository
{
    public function append(OutboxMessage $message): never
    {
        throw new RuntimeException('Injected failure before outbox persistence.');
    }
}
