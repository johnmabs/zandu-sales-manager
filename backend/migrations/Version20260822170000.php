<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped organization invitations with RLS';
    }
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE identity_access.organization_invitations (
 id UUID NOT NULL, organization_id UUID NOT NULL, email VARCHAR(254) NOT NULL,
 invited_by UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMPTZ NOT NULL,
 status VARCHAR(16) NOT NULL, intended_role_assignments JSON NOT NULL,
 accepted_by UUID DEFAULT NULL, accepted_at TIMESTAMPTZ DEFAULT NULL, version INT NOT NULL,
 PRIMARY KEY (id), CONSTRAINT invitation_token_hash_unique UNIQUE (token_hash),
 CONSTRAINT invitation_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id),
 CONSTRAINT invitation_status_check CHECK (status IN ('PENDING','ACCEPTED','EXPIRED','CANCELLED')),
 CONSTRAINT invitation_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX invitation_tenant_email_idx ON identity_access.organization_invitations (organization_id, email)');
        $this->addSql("CREATE UNIQUE INDEX invitation_one_pending_email_idx ON identity_access.organization_invitations (organization_id, email) WHERE status = 'PENDING'");
        $this->addSql('GRANT USAGE ON SCHEMA identity_access TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON identity_access.organization_invitations TO zandu_runtime');
        $this->addSql('ALTER TABLE identity_access.organization_invitations ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE identity_access.organization_invitations FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY invitation_tenant_isolation ON identity_access.organization_invitations FOR ALL TO zandu_runtime
USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE identity_access.organization_invitations');
    }
}
