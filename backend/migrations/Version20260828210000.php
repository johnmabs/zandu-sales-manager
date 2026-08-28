<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned suppliers with optimistic versioning and PostgreSQL RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.supplier (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    name VARCHAR(160) NOT NULL,
    phone VARCHAR(64) NULL,
    email VARCHAR(254) NULL,
    address VARCHAR(500) NULL,
    notes TEXT NULL,
    status VARCHAR(16) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    created_by UUID NOT NULL,
    updated_at TIMESTAMPTZ NULL,
    updated_by UUID NULL,
    version INT DEFAULT 1 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT supplier_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT supplier_organization_fk FOREIGN KEY (organization_id)
        REFERENCES organization.organizations (id) ON DELETE RESTRICT,
    CONSTRAINT supplier_name_check CHECK (btrim(name) <> ''),
    CONSTRAINT supplier_status_check CHECK (status IN ('ACTIVE', 'INACTIVE', 'ARCHIVED')),
    CONSTRAINT supplier_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX supplier_tenant_name_idx ON purchasing.supplier (organization_id, name, id)');
        $this->addSql('GRANT USAGE ON SCHEMA purchasing TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE ON purchasing.supplier TO zandu_runtime');
        $this->addSql('ALTER TABLE purchasing.supplier ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE purchasing.supplier FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY supplier_tenant_isolation ON purchasing.supplier
    FOR ALL
    TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE purchasing.supplier');
    }
}
