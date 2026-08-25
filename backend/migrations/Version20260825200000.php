<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned product packagings with exact decimals, base uniqueness and RLS';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE catalog.product_packagings (
 id UUID NOT NULL, organization_id UUID NOT NULL, product_id UUID NOT NULL, base BOOLEAN NOT NULL,
 code VARCHAR(64) NOT NULL, name VARCHAR(160) NOT NULL, unit_id UUID NOT NULL,
 conversion_factor NUMERIC(30,12) NOT NULL, precision SMALLINT NOT NULL,
 minimum_quantity NUMERIC(30,12) NOT NULL, quantity_increment NUMERIC(30,12) NOT NULL,
 allowed_for_sale BOOLEAN NOT NULL, allowed_for_purchase BOOLEAN NOT NULL, status VARCHAR(16) NOT NULL,
 created_at TIMESTAMPTZ NOT NULL, created_by UUID NOT NULL, updated_at TIMESTAMPTZ NULL, updated_by UUID NULL,
 version INT DEFAULT 1 NOT NULL, PRIMARY KEY (id),
 CONSTRAINT product_packaging_code_unique UNIQUE (organization_id, product_id, code),
 CONSTRAINT product_packaging_product_fk FOREIGN KEY (organization_id, product_id) REFERENCES catalog.products (organization_id, id),
 CONSTRAINT product_packaging_unit_fk FOREIGN KEY (organization_id, unit_id) REFERENCES catalog.units_of_measure (organization_id, id),
 CONSTRAINT product_packaging_factor_check CHECK (conversion_factor > 0),
 CONSTRAINT product_packaging_base_factor_check CHECK (NOT base OR conversion_factor = 1),
 CONSTRAINT product_packaging_precision_check CHECK (precision BETWEEN 0 AND 12),
 CONSTRAINT product_packaging_quantities_check CHECK (minimum_quantity > 0 AND quantity_increment > 0),
 CONSTRAINT product_packaging_status_check CHECK (status IN ('ACTIVE','INACTIVE','ARCHIVED')),
 CONSTRAINT product_packaging_audit_check CHECK ((updated_at IS NULL) = (updated_by IS NULL)),
 CONSTRAINT product_packaging_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX product_packaging_base_unique ON catalog.product_packagings (organization_id, product_id) WHERE base');
        $this->addSql('CREATE INDEX product_packaging_product_idx ON catalog.product_packagings (organization_id, product_id, status)');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON catalog.product_packagings TO zandu_runtime');
        $this->addSql('ALTER TABLE catalog.product_packagings ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE catalog.product_packagings FORCE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY product_packaging_tenant_isolation ON catalog.product_packagings FOR ALL TO zandu_runtime USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid) WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE catalog.product_packagings');
    }
}
