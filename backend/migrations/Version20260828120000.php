<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped return sales linked to immutable original sale snapshots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales.sale_line ADD CONSTRAINT sale_line_return_reference_unique UNIQUE (organization_id, sale_id, id, product_id)');
        $this->addSql(<<<'SQL'
CREATE TABLE sales.return_sale (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    store_id UUID NOT NULL,
    sale_id UUID NOT NULL,
    status VARCHAR(24) NOT NULL,
    reason VARCHAR(255) NULL,
    business_date DATE NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    completed_by UUID NULL,
    completed_at TIMESTAMPTZ NULL,
    cancelled_by UUID NULL,
    cancelled_at TIMESTAMPTZ NULL,
    version INT NOT NULL,
    CONSTRAINT return_sale_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT return_sale_source_unique UNIQUE (organization_id, id, sale_id),
    CONSTRAINT return_sale_store_fk FOREIGN KEY (organization_id, store_id)
        REFERENCES organization.stores (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT return_sale_sale_fk FOREIGN KEY (organization_id, sale_id)
        REFERENCES sales.sale (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT return_sale_status_check CHECK (status IN ('DRAFT', 'COMPLETED', 'CANCELLED')),
    CONSTRAINT return_sale_version_check CHECK (version > 0),
    CONSTRAINT return_sale_completion_check CHECK (
        (status = 'COMPLETED' AND business_date IS NOT NULL AND completed_by IS NOT NULL AND completed_at IS NOT NULL AND cancelled_by IS NULL AND cancelled_at IS NULL)
        OR (status = 'CANCELLED' AND business_date IS NULL AND completed_by IS NULL AND completed_at IS NULL AND cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL)
        OR (status = 'DRAFT' AND business_date IS NULL AND completed_by IS NULL AND completed_at IS NULL AND cancelled_by IS NULL AND cancelled_at IS NULL)
    )
)
SQL);
        $this->addSql('CREATE INDEX return_sale_source_idx ON sales.return_sale (organization_id, sale_id, created_at)');
        $this->addSql(<<<'SQL'
CREATE TABLE sales.return_sale_line (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    return_sale_id UUID NOT NULL,
    sale_id UUID NOT NULL,
    line_number INT NOT NULL,
    sale_line_id UUID NOT NULL,
    product_id UUID NOT NULL,
    returned_quantity NUMERIC(30,12) NOT NULL,
    base_returned_quantity NUMERIC(30,12) NOT NULL,
    restock BOOLEAN NOT NULL,
    reason VARCHAR(255) NULL,
    CONSTRAINT return_sale_line_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT return_sale_line_number_unique UNIQUE (organization_id, return_sale_id, line_number),
    CONSTRAINT return_sale_line_original_unique UNIQUE (organization_id, return_sale_id, sale_line_id),
    CONSTRAINT return_sale_line_return_fk FOREIGN KEY (organization_id, return_sale_id, sale_id)
        REFERENCES sales.return_sale (organization_id, id, sale_id) ON DELETE RESTRICT,
    CONSTRAINT return_sale_line_original_fk FOREIGN KEY (organization_id, sale_id, sale_line_id, product_id)
        REFERENCES sales.sale_line (organization_id, sale_id, id, product_id) ON DELETE RESTRICT,
    CONSTRAINT return_sale_line_number_check CHECK (line_number > 0),
    CONSTRAINT return_sale_line_quantity_check CHECK (returned_quantity > 0 AND base_returned_quantity > 0)
)
SQL);
        $this->addSql('CREATE INDEX return_sale_line_original_idx ON sales.return_sale_line (organization_id, sale_line_id)');
        $this->addSql('GRANT SELECT, INSERT, UPDATE ON sales.return_sale TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT ON sales.return_sale_line TO zandu_runtime');
        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        foreach (['return_sale', 'return_sale_line'] as $table) {
            $this->addSql("ALTER TABLE sales.$table ENABLE ROW LEVEL SECURITY");
            $this->addSql("ALTER TABLE sales.$table FORCE ROW LEVEL SECURITY");
            $this->addSql("CREATE POLICY {$table}_tenant_isolation ON sales.$table FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sales.return_sale_line');
        $this->addSql('DROP TABLE sales.return_sale');
        $this->addSql('ALTER TABLE sales.sale_line DROP CONSTRAINT sale_line_return_reference_unique');
    }
}
