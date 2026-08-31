<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist stock count snapshot lines and exclusive open scopes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory.stock_count ADD CONSTRAINT stock_count_tenant_store_unique UNIQUE (organization_id,id,store_id)');
        $this->addSql(<<<'SQL'
CREATE TABLE inventory.stock_count_line (
 id UUID NOT NULL, organization_id UUID NOT NULL, stock_count_id UUID NOT NULL, store_id UUID NOT NULL, product_id UUID NOT NULL,
 expected_quantity NUMERIC(30,12) NOT NULL, counted_quantity NUMERIC(30,12) NULL,
 counted_by UUID NULL, counted_at TIMESTAMPTZ NULL, revision INT NOT NULL,
 reconciliation_status VARCHAR(16) NOT NULL, version INT NOT NULL,
 PRIMARY KEY (id),
 CONSTRAINT stock_count_line_tenant_unique UNIQUE (organization_id,id),
 CONSTRAINT stock_count_line_product_unique UNIQUE (organization_id,stock_count_id,product_id),
 CONSTRAINT stock_count_line_count_fk FOREIGN KEY (organization_id,stock_count_id,store_id) REFERENCES inventory.stock_count (organization_id,id,store_id) ON DELETE RESTRICT,
 CONSTRAINT stock_count_line_product_fk FOREIGN KEY (organization_id,product_id) REFERENCES catalog.products (organization_id,id),
 CONSTRAINT stock_count_line_expected_check CHECK (expected_quantity>=0),
 CONSTRAINT stock_count_line_counted_check CHECK (counted_quantity IS NULL OR counted_quantity>=0),
 CONSTRAINT stock_count_line_counted_audit_check CHECK ((counted_quantity IS NULL AND counted_by IS NULL AND counted_at IS NULL) OR (counted_quantity IS NOT NULL AND counted_by IS NOT NULL AND counted_at IS NOT NULL)),
 CONSTRAINT stock_count_line_revision_check CHECK (revision>=0),
 CONSTRAINT stock_count_line_reconciliation_check CHECK (reconciliation_status IN ('PENDING','RECONCILED')),
 CONSTRAINT stock_count_line_version_check CHECK (version>0)
)
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE inventory.open_stock_count_scope (
 organization_id UUID NOT NULL, store_id UUID NOT NULL, product_id UUID NOT NULL, stock_count_id UUID NOT NULL,
 PRIMARY KEY (organization_id,store_id,product_id),
 CONSTRAINT open_stock_count_scope_count_fk FOREIGN KEY (organization_id,stock_count_id,store_id) REFERENCES inventory.stock_count (organization_id,id,store_id) ON DELETE RESTRICT,
 CONSTRAINT open_stock_count_scope_product_fk FOREIGN KEY (organization_id,product_id) REFERENCES catalog.products (organization_id,id)
)
SQL);
        $this->addSql('CREATE INDEX stock_count_line_reconciliation_idx ON inventory.stock_count_line (organization_id,stock_count_id,reconciliation_status,product_id)');
        $this->addSql('CREATE INDEX open_stock_count_scope_count_idx ON inventory.open_stock_count_scope (organization_id,stock_count_id)');
        foreach (['stock_count_line', 'open_stock_count_scope'] as $table) {
            $this->addSql(sprintf('GRANT SELECT,INSERT,UPDATE,DELETE ON inventory.%s TO zandu_runtime', $table));
            $this->addSql(sprintf('ALTER TABLE inventory.%s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE inventory.%s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf("CREATE POLICY %s_tenant_isolation ON inventory.%s FOR ALL TO zandu_runtime USING (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid) WITH CHECK (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid)", $table, $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inventory.open_stock_count_scope');
        $this->addSql('DROP TABLE inventory.stock_count_line');
        $this->addSql('ALTER TABLE inventory.stock_count DROP CONSTRAINT stock_count_tenant_store_unique');
    }
}
