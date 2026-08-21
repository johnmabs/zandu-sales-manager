<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped organization memberships with RLS';
    }
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE identity_access.organization_memberships (
 id UUID NOT NULL, organization_id UUID NOT NULL, user_id UUID NOT NULL, status VARCHAR(16) NOT NULL,
 role_assignments JSON NOT NULL, authorization_version INT NOT NULL, created_by UUID NOT NULL,
 created_at TIMESTAMPTZ NOT NULL, updated_by UUID NOT NULL, updated_at TIMESTAMPTZ NOT NULL, version INT NOT NULL,
 PRIMARY KEY (id), CONSTRAINT membership_tenant_user_unique UNIQUE (organization_id, user_id),
 CONSTRAINT membership_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id),
 CONSTRAINT membership_status_check CHECK (status IN ('INVITED','ACTIVE','SUSPENDED','REVOKED')),
 CONSTRAINT membership_versions_check CHECK (version > 0 AND authorization_version > 0)
)
SQL);
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON identity_access.organization_memberships TO zandu_runtime');
        $this->addSql('ALTER TABLE identity_access.organization_memberships ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE identity_access.organization_memberships FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY membership_tenant_isolation ON identity_access.organization_memberships FOR ALL TO zandu_runtime
USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE identity_access.organization_memberships');
    }
}
