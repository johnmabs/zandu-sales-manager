<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add append-only tenant security audit entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS security');
        $this->addSql(<<<'SQL'
CREATE TABLE security.security_audit_entries (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    actor_id UUID NOT NULL,
    actor_type VARCHAR(32) NOT NULL,
    user_id UUID DEFAULT NULL,
    action VARCHAR(96) NOT NULL,
    target_type VARCHAR(64) NOT NULL,
    target_id UUID NOT NULL,
    outcome VARCHAR(16) NOT NULL,
    reason VARCHAR(512) DEFAULT NULL,
    metadata JSONB NOT NULL,
    correlation_id UUID NOT NULL,
    causation_id UUID DEFAULT NULL,
    session_id UUID DEFAULT NULL,
    ip_address INET DEFAULT NULL,
    user_agent VARCHAR(512) DEFAULT NULL,
    occurred_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT security_audit_organization_fk FOREIGN KEY (organization_id)
        REFERENCES organization.organizations (id),
    CONSTRAINT security_audit_actor_type_check CHECK (actor_type IN ('User', 'ServiceAccount', 'System')),
    CONSTRAINT security_audit_outcome_check CHECK (outcome IN ('SUCCESS', 'DENIED', 'FAILED'))
)
SQL);
        $this->addSql('CREATE INDEX security_audit_tenant_time_idx ON security.security_audit_entries (organization_id, occurred_at DESC)');
        $this->addSql('CREATE INDEX security_audit_correlation_idx ON security.security_audit_entries (organization_id, correlation_id)');
        $this->addSql('GRANT USAGE ON SCHEMA security TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT ON security.security_audit_entries TO zandu_runtime');
        $this->addSql('ALTER TABLE security.security_audit_entries ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE security.security_audit_entries FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY security_audit_tenant_select ON security.security_audit_entries
    FOR SELECT TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY security_audit_tenant_insert ON security.security_audit_entries
    FOR INSERT TO zandu_runtime
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE security.security_audit_entries');
        $this->addSql('DROP SCHEMA security');
    }
}
