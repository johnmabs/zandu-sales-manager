<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned purchase returns and immutable line snapshots';
    }
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.purchase_return (
 id UUID NOT NULL, organization_id UUID NOT NULL, source_store_id UUID NOT NULL, supplier_id UUID NOT NULL,
 goods_receipt_id UUID NULL, purchase_order_id UUID NULL, status VARCHAR(16) NOT NULL, reason VARCHAR(500) NOT NULL,
 created_by UUID NOT NULL, created_at TIMESTAMPTZ NOT NULL, shipped_by UUID NULL, shipped_at TIMESTAMPTZ NULL,
 cancelled_by UUID NULL, cancelled_at TIMESTAMPTZ NULL, version INT NOT NULL, PRIMARY KEY (id),
 CONSTRAINT purchase_return_tenant_id_unique UNIQUE (organization_id,id),
 CONSTRAINT purchase_return_store_fk FOREIGN KEY (organization_id,source_store_id) REFERENCES organization.stores (organization_id,id) ON DELETE RESTRICT,
 CONSTRAINT purchase_return_supplier_fk FOREIGN KEY (organization_id,supplier_id) REFERENCES purchasing.supplier (organization_id,id) ON DELETE RESTRICT,
 CONSTRAINT purchase_return_receipt_fk FOREIGN KEY (organization_id,goods_receipt_id) REFERENCES purchasing.goods_receipt (organization_id,id) ON DELETE RESTRICT,
 CONSTRAINT purchase_return_order_fk FOREIGN KEY (organization_id,purchase_order_id) REFERENCES purchasing.purchase_order (organization_id,id) ON DELETE RESTRICT,
 CONSTRAINT purchase_return_source_check CHECK (purchase_order_id IS NULL OR goods_receipt_id IS NOT NULL),
 CONSTRAINT purchase_return_status_check CHECK (status IN ('DRAFT','SHIPPED','CANCELLED')),
 CONSTRAINT purchase_return_reason_check CHECK (length(btrim(reason)) BETWEEN 1 AND 500),
 CONSTRAINT purchase_return_terminal_check CHECK ((status='SHIPPED')=(shipped_at IS NOT NULL) AND (status='CANCELLED')=(cancelled_at IS NOT NULL) AND (shipped_by IS NULL)=(shipped_at IS NULL) AND (cancelled_by IS NULL)=(cancelled_at IS NULL)),
 CONSTRAINT purchase_return_version_check CHECK (version > 0)
)
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.purchase_return_line (
 id UUID NOT NULL, organization_id UUID NOT NULL, purchase_return_id UUID NOT NULL, product_id UUID NOT NULL,
 base_quantity NUMERIC(30,12) NOT NULL, goods_receipt_line_id UUID NULL, PRIMARY KEY (id),
 CONSTRAINT purchase_return_line_tenant_id_unique UNIQUE (organization_id,id),
 CONSTRAINT purchase_return_line_product_unique UNIQUE (organization_id,purchase_return_id,product_id),
 CONSTRAINT purchase_return_line_return_fk FOREIGN KEY (organization_id,purchase_return_id) REFERENCES purchasing.purchase_return (organization_id,id) ON DELETE RESTRICT,
 CONSTRAINT purchase_return_line_product_fk FOREIGN KEY (organization_id,product_id) REFERENCES catalog.products (organization_id,id) ON DELETE RESTRICT,
 CONSTRAINT purchase_return_line_receipt_line_fk FOREIGN KEY (organization_id,goods_receipt_line_id) REFERENCES purchasing.goods_receipt_line (organization_id,id) ON DELETE RESTRICT,
 CONSTRAINT purchase_return_line_quantity_check CHECK (base_quantity > 0)
)
SQL);
        $this->addSql('CREATE INDEX purchase_return_source_idx ON purchasing.purchase_return (organization_id,goods_receipt_id,status,id)');
        $this->addSql(<<<'SQL'
CREATE FUNCTION purchasing.guard_purchase_return_line_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE return_status VARCHAR(16);
BEGIN
 SELECT status INTO return_status FROM purchasing.purchase_return WHERE organization_id=COALESCE(NEW.organization_id,OLD.organization_id) AND id=COALESCE(NEW.purchase_return_id,OLD.purchase_return_id);
 IF return_status <> 'DRAFT' THEN RAISE EXCEPTION 'Purchase return lines can only change while DRAFT'; END IF;
 IF TG_OP='DELETE' THEN RETURN OLD; END IF; RETURN NEW;
END; $$
SQL);
        $this->addSql('CREATE TRIGGER purchase_return_line_mutation_guard BEFORE INSERT OR UPDATE OR DELETE ON purchasing.purchase_return_line FOR EACH ROW EXECUTE FUNCTION purchasing.guard_purchase_return_line_mutation()');
        $this->addSql('GRANT SELECT,INSERT,UPDATE ON purchasing.purchase_return TO zandu_runtime');
        $this->addSql('GRANT SELECT,INSERT,UPDATE,DELETE ON purchasing.purchase_return_line TO zandu_runtime');
        foreach (['purchase_return','purchase_return_line'] as $table) {
            $this->addSql(sprintf('ALTER TABLE purchasing.%s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE purchasing.%s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf("CREATE POLICY %s_tenant_isolation ON purchasing.%s FOR ALL TO zandu_runtime USING (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid) WITH CHECK (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid)", $table, $table));
        }
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE purchasing.purchase_return_line');
        $this->addSql('DROP FUNCTION purchasing.guard_purchase_return_line_mutation()');
        $this->addSql('DROP TABLE purchasing.purchase_return');
    }
}
