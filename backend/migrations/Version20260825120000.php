<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned products with relational integrity, optimistic locking and PostgreSQL RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog.units_of_measure ADD CONSTRAINT unit_of_measure_tenant_id_unique UNIQUE (organization_id, id)');
        $this->addSql(<<<'SQL'
CREATE TABLE catalog.products (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    product_code VARCHAR(64) NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT DEFAULT NULL,
    status VARCHAR(16) NOT NULL,
    type VARCHAR(16) NOT NULL,
    base_unit_id UUID NOT NULL,
    inventory_tracked BOOLEAN NOT NULL,
    tax_category_id UUID DEFAULT NULL,
    category_id UUID DEFAULT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    created_by UUID NOT NULL,
    activated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    activated_by UUID DEFAULT NULL,
    updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
    updated_by UUID DEFAULT NULL,
    version INT DEFAULT 1 NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT product_code_tenant_unique UNIQUE (organization_id, product_code),
    CONSTRAINT product_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT product_organization_fk FOREIGN KEY (organization_id)
        REFERENCES organization.organizations (id),
    CONSTRAINT product_base_unit_fk FOREIGN KEY (organization_id, base_unit_id)
        REFERENCES catalog.units_of_measure (organization_id, id),
    CONSTRAINT product_category_fk FOREIGN KEY (organization_id, category_id)
        REFERENCES catalog.categories (organization_id, id),
    CONSTRAINT product_code_check CHECK (LENGTH(BTRIM(product_code)) BETWEEN 1 AND 64),
    CONSTRAINT product_name_check CHECK (LENGTH(BTRIM(name)) BETWEEN 1 AND 160),
    CONSTRAINT product_status_check CHECK (status IN ('DRAFT', 'ACTIVE', 'INACTIVE', 'ARCHIVED')),
    CONSTRAINT product_type_check CHECK (type IN ('PHYSICAL', 'SERVICE')),
    CONSTRAINT product_service_inventory_check CHECK (type <> 'SERVICE' OR inventory_tracked = FALSE),
    CONSTRAINT product_activation_audit_check CHECK ((activated_at IS NULL) = (activated_by IS NULL)),
    CONSTRAINT product_activated_status_check CHECK (status NOT IN ('ACTIVE', 'INACTIVE') OR activated_at IS NOT NULL),
    CONSTRAINT product_update_audit_check CHECK ((updated_at IS NULL) = (updated_by IS NULL)),
    CONSTRAINT product_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX product_tenant_status_idx ON catalog.products (organization_id, status)');
        $this->addSql('CREATE INDEX product_tenant_category_idx ON catalog.products (organization_id, category_id)');
        $this->addSql('CREATE INDEX product_tenant_base_unit_idx ON catalog.products (organization_id, base_unit_id)');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON catalog.products TO zandu_runtime');
        $this->addSql('ALTER TABLE catalog.products ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE catalog.products FORCE ROW LEVEL SECURITY');
        $this->addSql(<<<'SQL'
CREATE POLICY product_tenant_isolation ON catalog.products
    FOR ALL
    TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE catalog.products');
        $this->addSql('ALTER TABLE catalog.units_of_measure DROP CONSTRAINT unit_of_measure_tenant_id_unique');
    }
}
