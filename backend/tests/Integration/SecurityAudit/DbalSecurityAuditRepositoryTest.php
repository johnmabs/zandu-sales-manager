<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\SecurityAudit;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DbalSecurityAuditRepository;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SecurityAuditEntryId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\SecurityAudit\ActorReference;
use Zandu\SharedKernel\SecurityAudit\AuditOutcome;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditEntry;
use Zandu\Tests\Integration\PostgresTestCase;

final class DbalSecurityAuditRepositoryTest extends PostgresTestCase
{
    private const ORGANIZATION_ID = '0198e421-147c-72d5-b75a-a936797ff9c8';
    private const ENTRY_ID = '0198e422-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198e423-147c-72d5-b75a-a936797ff9c8';
    private const CORRELATION_ID = '0198e424-147c-72d5-b75a-a936797ff9c8';

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->executeStatement('DELETE FROM security.security_audit_entries WHERE organization_id = ?', [self::ORGANIZATION_ID]);
        $this->connection->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (
    id, name, status, country_code, default_currency, default_time_zone, default_locale,
    created_by, created_at, updated_by, updated_at, version
) VALUES (?, 'Audit tenant', 'ACTIVE', 'CG', 'XAF', 'Africa/Brazzaville', 'fr_CG', ?, NOW(), ?, NOW(), 1)
ON CONFLICT (id) DO NOTHING
SQL, [self::ORGANIZATION_ID, self::ACTOR_ID, self::ACTOR_ID]);
    }

    public function testRuntimeCanAppendButCannotMutateAnAuditEntry(): void
    {
        $this->connection->beginTransaction();
        try {
            $this->connection->executeStatement("SET LOCAL ROLE zandu_runtime");
            $this->connection->executeStatement("SELECT set_config('app.organization_id', ?, true)", [self::ORGANIZATION_ID]);
            (new DbalSecurityAuditRepository($this->connection))->append($this->entry());
            self::assertSame('ROLE_ASSIGNED', $this->connection->fetchOne(
                'SELECT action FROM security.security_audit_entries WHERE id = ?',
                [self::ENTRY_ID],
            ));

            $this->expectException(Exception::class);
            $this->connection->executeStatement(
                'UPDATE security.security_audit_entries SET reason = ? WHERE id = ?',
                ['tampered', self::ENTRY_ID],
            );
        } finally {
            $this->connection->rollBack();
        }
    }

    private function entry(): SecurityAuditEntry
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString(self::ORGANIZATION_ID, $factory);

        return new SecurityAuditEntry(
            SecurityAuditEntryId::fromString(self::ENTRY_ID, $factory),
            $organizationId,
            new ActorReference(ActorId::fromString(self::ACTOR_ID, $factory), ActorType::User, null),
            SecurityAction::RoleAssigned,
            ResourceReference::for('organization_membership', $organizationId),
            AuditOutcome::Success,
            null,
            SafeAuditMetadata::empty(),
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            null,
            null,
            '127.0.0.1',
            'PHPUnit',
            new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
        );
    }
}
