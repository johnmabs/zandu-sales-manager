<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Tenancy;

use Doctrine\DBAL\Exception as DbalException;
use RuntimeException;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\Tests\Integration\PostgresTestCase;

final class PostgresRowLevelSecurityTest extends PostgresTestCase
{
    private const ORGANIZATION_A = '0198d1b4-82de-7cd0-aa1e-fbdd96522a84';
    private const ORGANIZATION_B = '0198d1b5-1486-74dc-b699-004b6420c93f';
    private const ORGANIZATION_C = '0198d1b6-4598-7f53-950f-45e51eed78b7';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseAvailable = true;
        $this->deleteFixtures();
        $this->insertOrganization(self::ORGANIZATION_A, 'Tenant A');
        $this->insertOrganization(self::ORGANIZATION_B, 'Tenant B');
    }

    protected function tearDown(): void
    {
        if ($this->databaseAvailable) {
            $this->deleteFixtures();
        }

        parent::tearDown();
    }

    public function testUnfilteredQueryOnlyReturnsTheCurrentTenant(): void
    {
        $visibleIds = $this->transactions()->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): array => $this->connection->fetchFirstColumn(
                'SELECT id FROM organization.organizations ORDER BY id',
            ),
        );

        self::assertSame([self::ORGANIZATION_A], $visibleIds);
    }

    public function testTenantTransactionUsesRestrictedRuntimeRole(): void
    {
        $role = $this->transactions()->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): array => [
                'current_user' => $this->connection->fetchOne('SELECT current_user'),
                'rolsuper' => $this->connection->fetchOne(
                    'SELECT rolsuper FROM pg_roles WHERE rolname = current_user',
                ),
                'rolbypassrls' => $this->connection->fetchOne(
                    'SELECT rolbypassrls FROM pg_roles WHERE rolname = current_user',
                ),
            ],
        );

        self::assertSame('zandu_runtime', $role['current_user'] ?? null);
        self::assertFalse($role['rolsuper'] ?? true);
        self::assertFalse($role['rolbypassrls'] ?? true);
    }

    public function testEveryTenantOwnedTableForcesRowLevelSecurity(): void
    {
        $tables = $this->connection->fetchAllAssociative(<<<'SQL'
SELECT namespace.nspname AS schema_name, relation.relname AS table_name,
       relation.relrowsecurity, relation.relforcerowsecurity
FROM pg_class relation
JOIN pg_namespace namespace ON namespace.oid = relation.relnamespace
WHERE (namespace.nspname, relation.relname) IN (
    ('organization', 'organizations'),
    ('organization', 'stores'),
    ('organization', 'store_closures'),
    ('identity_access', 'organization_invitations'),
    ('identity_access', 'organization_memberships'),
    ('catalog', 'categories'),
    ('catalog', 'units_of_measure'),
    ('security', 'security_audit_entries'),
    ('messaging', 'outbox_messages')
)
ORDER BY namespace.nspname, relation.relname
SQL);

        self::assertCount(9, $tables);
        foreach ($tables as $table) {
            self::assertTrue((bool) $table['relrowsecurity'], sprintf('%s.%s must enable RLS.', $table['schema_name'], $table['table_name']));
            self::assertTrue((bool) $table['relforcerowsecurity'], sprintf('%s.%s must force RLS.', $table['schema_name'], $table['table_name']));
        }
    }

    public function testMissingTenantContextIsFailClosed(): void
    {
        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement('SET LOCAL ROLE zandu_runtime');
            self::assertSame(0, $this->connection->fetchOne('SELECT COUNT(*) FROM organization.organizations'));
        } finally {
            $this->connection->rollBack();
        }
    }

    public function testCrossTenantWriteIsRejectedByWithCheck(): void
    {
        $this->expectException(DbalException::class);

        $this->transactions()->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn() => $this->insertOrganization(self::ORGANIZATION_C, 'Forbidden tenant'),
        );
    }

    public function testTenantContextDoesNotLeakAfterCommit(): void
    {
        $this->transactions()->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): string => (string) $this->connection->fetchOne("SELECT current_setting('app.organization_id')"),
        );

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement('SET LOCAL ROLE zandu_runtime');
            self::assertSame('', $this->connection->fetchOne("SELECT current_setting('app.organization_id', true)"));
            self::assertSame(0, $this->connection->fetchOne('SELECT COUNT(*) FROM organization.organizations'));
        } finally {
            $this->connection->rollBack();
        }
    }

    public function testTenantContextDoesNotLeakAfterRollback(): void
    {
        try {
            $this->transactions()->transactional(
                $this->organizationId(self::ORGANIZATION_A),
                static fn(): never => throw new RuntimeException('Injected failure.'),
            );
        } catch (RuntimeException) {
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement('SET LOCAL ROLE zandu_runtime');
            self::assertSame('', $this->connection->fetchOne("SELECT current_setting('app.organization_id', true)"));
        } finally {
            $this->connection->rollBack();
        }
    }

    public function testTenantContextIsIsolatedBetweenConnections(): void
    {
        $secondConnection = $this->secondConnection();
        $this->connection->beginTransaction();
        $secondConnection->beginTransaction();

        try {
            $this->connection->executeStatement('SET LOCAL ROLE zandu_runtime');
            $this->connection->executeStatement("SELECT set_config('app.organization_id', ?, true)", [self::ORGANIZATION_A]);
            $secondConnection->executeStatement('SET LOCAL ROLE zandu_runtime');

            self::assertSame(self::ORGANIZATION_A, $this->connection->fetchOne("SELECT current_setting('app.organization_id')"));
            self::assertNull($secondConnection->fetchOne("SELECT current_setting('app.organization_id', true)"));
            self::assertSame(0, $secondConnection->fetchOne('SELECT COUNT(*) FROM organization.organizations'));
        } finally {
            $this->connection->rollBack();
            $secondConnection->rollBack();
            $secondConnection->close();
        }
    }

    private function transactions(): DoctrineTenantTransaction
    {
        return new DoctrineTenantTransaction($this->connection, 'zandu_runtime');
    }

    private function organizationId(string $value): OrganizationId
    {
        return OrganizationId::fromString($value, new SymfonyUuidFactory());
    }

    private function insertOrganization(string $id, string $name): void
    {
        $this->connection->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (
    id, name, status, country_code, default_currency, default_time_zone,
    default_locale, created_by, created_at, updated_by, updated_at, version
) VALUES (?, ?, 'ACTIVE', 'CG', 'XAF', 'Africa/Brazzaville', 'fr_CG', ?, NOW(), ?, NOW(), 1)
SQL, [$id, $name, self::ACTOR_ID, self::ACTOR_ID]);
    }

    private function deleteFixtures(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM organization.organizations WHERE id IN (?, ?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B, self::ORGANIZATION_C],
        );
    }
}
