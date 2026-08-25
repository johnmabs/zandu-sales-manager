<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned category hierarchies with constraints and PostgreSQL RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE catalog.categories (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    name VARCHAR(160) NOT NULL,
    parent_category_id UUID DEFAULT NULL,
    status VARCHAR(16) NOT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    created_by UUID NOT NULL,
    updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    updated_by UUID DEFAULT NULL,
    version INT DEFAULT 1 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT category_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT category_organization_fk FOREIGN KEY (organization_id)
        REFERENCES organization.organizations (id),
    CONSTRAINT category_parent_fk FOREIGN KEY (organization_id, parent_category_id)
        REFERENCES catalog.categories (organization_id, id),
    CONSTRAINT category_not_own_parent_check CHECK (parent_category_id IS NULL OR parent_category_id <> id),
    CONSTRAINT category_name_check CHECK (LENGTH(BTRIM(name)) BETWEEN 1 AND 160),
    CONSTRAINT category_status_check CHECK (status IN ('ACTIVE', 'INACTIVE', 'ARCHIVED')),
    CONSTRAINT category_update_audit_check CHECK ((updated_at IS NULL) = (updated_by IS NULL)),
    CONSTRAINT category_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX category_tenant_idx ON catalog.categories (organization_id)');
        $this->addSql('CREATE INDEX category_tenant_parent_idx ON catalog.categories (organization_id, parent_category_id)');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON catalog.categories TO zandu_runtime');
        $this->addSql('ALTER TABLE catalog.categories ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE catalog.categories FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY category_tenant_isolation ON catalog.categories
    FOR ALL
    TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE catalog.categories');
    }
}
