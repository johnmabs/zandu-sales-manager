<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenant-scoped Sales foundation tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS sales');
        $this->addSql("CREATE TABLE sales.sale (id UUID PRIMARY KEY, organization_id UUID NOT NULL, store_id UUID NOT NULL, status VARCHAR(24) NOT NULL, currency VARCHAR(3) NOT NULL, customer_id UUID NULL, subtotal NUMERIC(30,12) NOT NULL DEFAULT 0, discount_total NUMERIC(30,12) NOT NULL DEFAULT 0, tax_total NUMERIC(30,12) NOT NULL DEFAULT 0, total NUMERIC(30,12) NOT NULL DEFAULT 0, business_date DATE NULL, created_by UUID NOT NULL, created_at TIMESTAMPTZ NOT NULL, completed_by UUID NULL, completed_at TIMESTAMPTZ NULL, cancelled_by UUID NULL, cancelled_at TIMESTAMPTZ NULL, version INT NOT NULL DEFAULT 1, CONSTRAINT sale_tenant_id_unique UNIQUE (organization_id,id), CONSTRAINT sale_status_check CHECK (status IN ('DRAFT','AWAITING_PAYMENT','COMPLETED','CANCELLED')), CONSTRAINT sale_amounts_check CHECK (subtotal >= 0 AND discount_total >= 0 AND tax_total >= 0 AND total >= 0), CONSTRAINT sale_version_check CHECK (version > 0), CONSTRAINT sale_store_fk FOREIGN KEY (organization_id,store_id) REFERENCES organization.stores(organization_id,id))");
        $this->addSql('CREATE INDEX sale_tenant_store_status_idx ON sales.sale (organization_id, store_id, status)');
        $this->addSql('CREATE INDEX sale_tenant_business_date_idx ON sales.sale (organization_id, business_date)');
        $this->addSql("CREATE TABLE sales.sale_line (id UUID PRIMARY KEY, organization_id UUID NOT NULL, sale_id UUID NOT NULL, line_number INT NOT NULL, product_id UUID NOT NULL, product_packaging_id UUID NOT NULL, product_code_snapshot VARCHAR(64) NULL, product_name_snapshot VARCHAR(160) NULL, packaging_code_snapshot VARCHAR(64) NOT NULL, packaging_name_snapshot VARCHAR(160) NULL, unit_id_snapshot UUID NOT NULL, entered_quantity NUMERIC(30,12) NOT NULL, conversion_factor_snapshot NUMERIC(30,12) NOT NULL, base_quantity NUMERIC(30,12) NOT NULL, unit_price NUMERIC(30,12) NOT NULL, price_list_id UUID NULL, product_price_id UUID NULL, discount_amount NUMERIC(30,12) NOT NULL DEFAULT 0, taxable_amount NUMERIC(30,12) NOT NULL DEFAULT 0, tax_amount NUMERIC(30,12) NOT NULL DEFAULT 0, subtotal NUMERIC(30,12) NOT NULL DEFAULT 0, total NUMERIC(30,12) NOT NULL DEFAULT 0, source_versions JSONB NOT NULL DEFAULT '{}'::jsonb, CONSTRAINT sale_line_tenant_id_unique UNIQUE (organization_id,id), CONSTRAINT sale_line_sale_fk FOREIGN KEY (organization_id,sale_id) REFERENCES sales.sale(organization_id,id) ON DELETE RESTRICT, CONSTRAINT sale_line_number_unique UNIQUE (organization_id,sale_id,line_number), CONSTRAINT sale_line_quantities_check CHECK (entered_quantity > 0 AND conversion_factor_snapshot > 0 AND base_quantity > 0), CONSTRAINT sale_line_amounts_check CHECK (unit_price >= 0 AND discount_amount >= 0 AND taxable_amount >= 0 AND tax_amount >= 0 AND subtotal >= 0 AND total >= 0))");
        $this->addSql('CREATE INDEX sale_line_tenant_sale_idx ON sales.sale_line (organization_id, sale_id, line_number)');
        $this->addSql('GRANT USAGE ON SCHEMA sales TO zandu_runtime');
        foreach (['sales.sale','sales.sale_line'] as $table) {
            $this->addSql(sprintf('GRANT SELECT, INSERT, UPDATE, DELETE ON %s TO zandu_runtime', $table));
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
        }
        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        $this->addSql("CREATE POLICY sale_tenant_isolation ON sales.sale FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
        $this->addSql("CREATE POLICY sale_line_tenant_isolation ON sales.sale_line FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sales.sale_line');
        $this->addSql('DROP TABLE IF EXISTS sales.sale');
    }
}
