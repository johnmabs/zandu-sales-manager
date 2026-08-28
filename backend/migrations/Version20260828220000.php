<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned purchase orders and lines with draft-only line mutation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.purchase_order (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    destination_store_id UUID NOT NULL,
    supplier_id UUID NOT NULL,
    number VARCHAR(64) NOT NULL,
    status VARCHAR(24) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    expected_total NUMERIC(30,6) NOT NULL,
    created_by UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    confirmed_by UUID NULL,
    confirmed_at TIMESTAMPTZ NULL,
    closed_by UUID NULL,
    closed_at TIMESTAMPTZ NULL,
    closed_reason VARCHAR(500) NULL,
    cancelled_by UUID NULL,
    cancelled_at TIMESTAMPTZ NULL,
    version INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT purchase_order_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT purchase_order_number_unique UNIQUE (organization_id, number),
    CONSTRAINT purchase_order_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id) ON DELETE RESTRICT,
    CONSTRAINT purchase_order_store_fk FOREIGN KEY (organization_id, destination_store_id) REFERENCES organization.stores (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT purchase_order_supplier_fk FOREIGN KEY (organization_id, supplier_id) REFERENCES purchasing.supplier (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT purchase_order_status_check CHECK (status IN ('DRAFT', 'CONFIRMED', 'PARTIALLY_RECEIVED', 'FULLY_RECEIVED', 'CLOSED', 'CANCELLED')),
    CONSTRAINT purchase_order_currency_check CHECK (currency ~ '^[A-Z]{3}$'),
    CONSTRAINT purchase_order_total_check CHECK (expected_total >= 0),
    CONSTRAINT purchase_order_confirmation_audit_check CHECK ((confirmed_by IS NULL) = (confirmed_at IS NULL)),
    CONSTRAINT purchase_order_close_audit_check CHECK ((closed_by IS NULL) = (closed_at IS NULL) AND (closed_at IS NULL) = (closed_reason IS NULL)),
    CONSTRAINT purchase_order_cancel_audit_check CHECK ((cancelled_by IS NULL) = (cancelled_at IS NULL)),
    CONSTRAINT purchase_order_version_check CHECK (version > 0)
)
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE purchasing.purchase_order_line (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    purchase_order_id UUID NOT NULL,
    product_id UUID NOT NULL,
    product_packaging_id UUID NULL,
    entered_ordered_quantity NUMERIC(30,12) NOT NULL,
    conversion_factor_snapshot NUMERIC(30,12) NOT NULL,
    ordered_base_quantity NUMERIC(30,12) NOT NULL,
    unit_cost NUMERIC(30,12) NOT NULL,
    inventory_unit_cost NUMERIC(30,12) NOT NULL,
    received_quantity NUMERIC(30,12) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT purchase_order_line_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT purchase_order_line_product_unique UNIQUE (organization_id, purchase_order_id, product_id),
    CONSTRAINT purchase_order_line_order_fk FOREIGN KEY (organization_id, purchase_order_id) REFERENCES purchasing.purchase_order (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT purchase_order_line_product_fk FOREIGN KEY (organization_id, product_id) REFERENCES catalog.products (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT purchase_order_line_packaging_fk FOREIGN KEY (organization_id, product_id, product_packaging_id) REFERENCES catalog.product_packagings (organization_id, product_id, id) ON DELETE RESTRICT,
    CONSTRAINT purchase_order_line_quantity_check CHECK (entered_ordered_quantity > 0 AND conversion_factor_snapshot > 0 AND ordered_base_quantity > 0),
    CONSTRAINT purchase_order_line_base_quantity_check CHECK (ordered_base_quantity = round(entered_ordered_quantity * conversion_factor_snapshot, 12)),
    CONSTRAINT purchase_order_line_cost_check CHECK (unit_cost >= 0 AND inventory_unit_cost >= 0),
    CONSTRAINT purchase_order_line_received_check CHECK (received_quantity >= 0 AND received_quantity <= ordered_base_quantity)
)
SQL);
        $this->addSql('CREATE INDEX purchase_order_tenant_status_idx ON purchasing.purchase_order (organization_id, status, created_at, id)');
        $this->addSql('CREATE INDEX purchase_order_line_order_idx ON purchasing.purchase_order_line (organization_id, purchase_order_id, id)');
        $this->addSql(<<<'SQL'
CREATE FUNCTION purchasing.guard_purchase_order_line_mutation() RETURNS trigger
LANGUAGE plpgsql AS $$
DECLARE order_status VARCHAR(24);
BEGIN
    SELECT status INTO order_status
    FROM purchasing.purchase_order
    WHERE organization_id = COALESCE(NEW.organization_id, OLD.organization_id)
      AND id = COALESCE(NEW.purchase_order_id, OLD.purchase_order_id);
    IF order_status <> 'DRAFT' AND (
        TG_OP <> 'UPDATE'
        OR NEW.id <> OLD.id
        OR NEW.organization_id <> OLD.organization_id
        OR NEW.purchase_order_id <> OLD.purchase_order_id
        OR NEW.product_id <> OLD.product_id
        OR NEW.product_packaging_id IS DISTINCT FROM OLD.product_packaging_id
        OR NEW.entered_ordered_quantity <> OLD.entered_ordered_quantity
        OR NEW.conversion_factor_snapshot <> OLD.conversion_factor_snapshot
        OR NEW.ordered_base_quantity <> OLD.ordered_base_quantity
        OR NEW.unit_cost <> OLD.unit_cost
        OR NEW.inventory_unit_cost <> OLD.inventory_unit_cost
    ) THEN
        RAISE EXCEPTION 'Purchase order lines can only be changed while the order is DRAFT';
    END IF;
    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$$
SQL);
        $this->addSql('CREATE TRIGGER purchase_order_line_mutation_guard BEFORE INSERT OR UPDATE OR DELETE ON purchasing.purchase_order_line FOR EACH ROW EXECUTE FUNCTION purchasing.guard_purchase_order_line_mutation()');
        $this->addSql('GRANT USAGE ON SCHEMA purchasing TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE ON purchasing.purchase_order TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT, UPDATE, DELETE ON purchasing.purchase_order_line TO zandu_runtime');
        foreach (['purchase_order', 'purchase_order_line'] as $table) {
            $this->addSql(sprintf('ALTER TABLE purchasing.%s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE purchasing.%s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf("CREATE POLICY %s_tenant_isolation ON purchasing.%s FOR ALL TO zandu_runtime USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid) WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)", $table, $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE purchasing.purchase_order_line');
        $this->addSql('DROP FUNCTION purchasing.guard_purchase_order_line_mutation()');
        $this->addSql('DROP TABLE purchasing.purchase_order');
    }
}
