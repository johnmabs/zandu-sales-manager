<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned goods receipts and immutable receipt line snapshots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE purchasing.purchase_order_line ADD CONSTRAINT purchase_order_line_order_id_unique UNIQUE (organization_id, purchase_order_id, id)');
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.goods_receipt (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    store_id UUID NOT NULL,
    supplier_id UUID NOT NULL,
    purchase_order_id UUID NULL,
    number VARCHAR(64) NOT NULL,
    status VARCHAR(16) NOT NULL,
    supplier_delivery_note VARCHAR(128) NULL,
    notes TEXT NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    posted_by UUID NULL,
    posted_at TIMESTAMPTZ NULL,
    cancelled_by UUID NULL,
    cancelled_at TIMESTAMPTZ NULL,
    version INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT goods_receipt_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT goods_receipt_number_unique UNIQUE (organization_id, number),
    CONSTRAINT goods_receipt_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_store_fk FOREIGN KEY (organization_id, store_id) REFERENCES organization.stores (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_supplier_fk FOREIGN KEY (organization_id, supplier_id) REFERENCES purchasing.supplier (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_order_fk FOREIGN KEY (organization_id, purchase_order_id) REFERENCES purchasing.purchase_order (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_status_check CHECK (status IN ('DRAFT', 'POSTED', 'CANCELLED')),
    CONSTRAINT goods_receipt_post_audit_check CHECK ((posted_by IS NULL) = (posted_at IS NULL)),
    CONSTRAINT goods_receipt_cancel_audit_check CHECK ((cancelled_by IS NULL) = (cancelled_at IS NULL)),
    CONSTRAINT goods_receipt_terminal_audit_check CHECK ((status = 'POSTED') = (posted_at IS NOT NULL) AND (status = 'CANCELLED') = (cancelled_at IS NOT NULL)),
    CONSTRAINT goods_receipt_version_check CHECK (version > 0)
)
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.goods_receipt_line (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    goods_receipt_id UUID NOT NULL,
    product_id UUID NOT NULL,
    product_packaging_id UUID NULL,
    entered_received_quantity NUMERIC(30,12) NOT NULL,
    conversion_factor_snapshot NUMERIC(30,12) NOT NULL,
    received_base_quantity NUMERIC(30,12) NOT NULL,
    actual_unit_cost NUMERIC(30,12) NULL,
    inventory_unit_cost NUMERIC(30,12) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    purchase_order_id UUID NULL,
    purchase_order_line_id UUID NULL,
    PRIMARY KEY (id),
    CONSTRAINT goods_receipt_line_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT goods_receipt_line_product_unique UNIQUE (organization_id, goods_receipt_id, product_id),
    CONSTRAINT goods_receipt_line_receipt_fk FOREIGN KEY (organization_id, goods_receipt_id) REFERENCES purchasing.goods_receipt (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_line_product_fk FOREIGN KEY (organization_id, product_id) REFERENCES catalog.products (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_line_packaging_fk FOREIGN KEY (organization_id, product_id, product_packaging_id) REFERENCES catalog.product_packagings (organization_id, product_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_line_order_line_fk FOREIGN KEY (organization_id, purchase_order_id, purchase_order_line_id) REFERENCES purchasing.purchase_order_line (organization_id, purchase_order_id, id) ON DELETE RESTRICT,
    CONSTRAINT goods_receipt_line_order_shape_check CHECK ((purchase_order_id IS NULL) = (purchase_order_line_id IS NULL)),
    CONSTRAINT goods_receipt_line_quantity_check CHECK (entered_received_quantity > 0 AND conversion_factor_snapshot > 0 AND received_base_quantity > 0),
    CONSTRAINT goods_receipt_line_base_quantity_check CHECK (received_base_quantity = round(entered_received_quantity * conversion_factor_snapshot, 12)),
    CONSTRAINT goods_receipt_line_cost_check CHECK (inventory_unit_cost >= 0 AND (actual_unit_cost IS NULL OR actual_unit_cost >= 0)),
    CONSTRAINT goods_receipt_line_inventory_cost_check CHECK (actual_unit_cost IS NULL OR inventory_unit_cost = round(actual_unit_cost / conversion_factor_snapshot, 12)),
    CONSTRAINT goods_receipt_line_currency_check CHECK (currency ~ '^[A-Z]{3}$')
)
SQL);
        $this->addSql('CREATE INDEX goods_receipt_tenant_status_idx ON purchasing.goods_receipt (organization_id, status, created_at, id)');
        $this->addSql('CREATE INDEX goods_receipt_line_receipt_idx ON purchasing.goods_receipt_line (organization_id, goods_receipt_id, id)');
        $this->addSql(<<<'SQL'
CREATE FUNCTION purchasing.guard_goods_receipt_line_mutation() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE receipt_status VARCHAR(16);
BEGIN
    SELECT status INTO receipt_status
    FROM purchasing.goods_receipt
    WHERE organization_id = COALESCE(NEW.organization_id, OLD.organization_id)
      AND id = COALESCE(NEW.goods_receipt_id, OLD.goods_receipt_id);
    IF receipt_status <> 'DRAFT' THEN
        RAISE EXCEPTION 'Goods receipt lines can only be changed while the receipt is DRAFT';
    END IF;
    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$
SQL);
        $this->addSql('CREATE TRIGGER goods_receipt_line_mutation_guard BEFORE INSERT OR UPDATE OR DELETE ON purchasing.goods_receipt_line FOR EACH ROW EXECUTE FUNCTION purchasing.guard_goods_receipt_line_mutation()');
        $this->addSql('GRANT SELECT, INSERT, UPDATE ON purchasing.goods_receipt TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON purchasing.goods_receipt_line TO zandu_runtime');
        foreach (['goods_receipt', 'goods_receipt_line'] as $table) {
            $this->addSql(sprintf('ALTER TABLE purchasing.%s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE purchasing.%s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf("CREATE POLICY %s_tenant_isolation ON purchasing.%s FOR ALL TO zandu_runtime USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid) WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)", $table, $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE purchasing.goods_receipt_line');
        $this->addSql('DROP FUNCTION purchasing.guard_goods_receipt_line_mutation()');
        $this->addSql('DROP TABLE purchasing.goods_receipt');
        $this->addSql('ALTER TABLE purchasing.purchase_order_line DROP CONSTRAINT purchase_order_line_order_id_unique');
    }
}
