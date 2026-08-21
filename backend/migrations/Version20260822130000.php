<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped stores with PostgreSQL RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE organization.stores (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    code VARCHAR(32) NOT NULL,
    name VARCHAR(160) NOT NULL,
    status VARCHAR(32) NOT NULL,
    address VARCHAR(500) DEFAULT NULL,
    time_zone VARCHAR(64) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    locale VARCHAR(16) NOT NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    updated_by UUID NOT NULL,
    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    suspended_by UUID DEFAULT NULL,
    suspended_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    status_before_closure VARCHAR(32) DEFAULT NULL,
    closure_requested_by UUID DEFAULT NULL,
    closure_requested_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    closed_by UUID DEFAULT NULL,
    closed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    version INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT store_organization_fk FOREIGN KEY (organization_id)
        REFERENCES organization.organizations (id),
    CONSTRAINT store_code_tenant_unique UNIQUE (organization_id, code),
    CONSTRAINT store_status_check CHECK (status IN ('ACTIVE', 'SUSPENDED', 'CLOSURE_PENDING', 'CLOSED')),
    CONSTRAINT store_previous_status_check CHECK (status_before_closure IS NULL OR status_before_closure IN ('ACTIVE', 'SUSPENDED')),
    CONSTRAINT store_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX store_tenant_idx ON organization.stores (organization_id)');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON organization.stores TO zandu_runtime');
        $this->addSql('ALTER TABLE organization.stores ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE organization.stores FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY store_tenant_isolation ON organization.stores
    FOR ALL
    TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organization.stores');
    }
}
