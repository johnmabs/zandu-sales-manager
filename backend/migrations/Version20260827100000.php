<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped inventory valuations and their append-only ledger';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS inventory_costing');
        $this->addSql('GRANT USAGE ON SCHEMA inventory_costing TO zandu_runtime');
        $this->addSql('ALTER TABLE inventory.stock ADD CONSTRAINT stock_costing_identity_unique UNIQUE (organization_id, id, store_id, product_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE inventory_costing.stock_valuation (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    store_id UUID NOT NULL,
    product_id UUID NOT NULL,
    stock_id UUID NOT NULL,
    quantity_on_hand NUMERIC(30,12) NOT NULL,
    total_value NUMERIC(30,6) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    version INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT stock_valuation_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT stock_valuation_stock_unique UNIQUE (organization_id, stock_id),
    CONSTRAINT stock_valuation_position_unique UNIQUE (organization_id, store_id, product_id),
    CONSTRAINT stock_valuation_ledger_identity_unique UNIQUE (organization_id, id, store_id, product_id, stock_id),
    CONSTRAINT stock_valuation_stock_fk FOREIGN KEY (organization_id, stock_id, store_id, product_id)
        REFERENCES inventory.stock (organization_id, id, store_id, product_id) ON DELETE RESTRICT,
    CONSTRAINT stock_valuation_quantity_check CHECK (quantity_on_hand >= 0),
    CONSTRAINT stock_valuation_value_check CHECK (total_value >= 0),
    CONSTRAINT stock_valuation_zero_check CHECK (quantity_on_hand <> 0 OR total_value = 0),
    CONSTRAINT stock_valuation_currency_check CHECK (currency ~ '^[A-Z]{3}$'),
    CONSTRAINT stock_valuation_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX stock_valuation_tenant_store_idx ON inventory_costing.stock_valuation (organization_id, store_id)');
        $this->addSql('CREATE INDEX stock_valuation_tenant_product_idx ON inventory_costing.stock_valuation (organization_id, product_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE inventory_costing.stock_valuation_movement (
    id UUID NOT NULL,
    stock_valuation_id UUID NOT NULL,
    organization_id UUID NOT NULL,
    store_id UUID NOT NULL,
    product_id UUID NOT NULL,
    stock_id UUID NOT NULL,
    stock_movement_id UUID NULL,
    type VARCHAR(32) NOT NULL,
    quantity NUMERIC(30,12) NOT NULL,
    unit_cost NUMERIC(30,12) NOT NULL,
    value NUMERIC(30,6) NOT NULL,
    previous_total_value NUMERIC(30,6) NOT NULL,
    resulting_total_value NUMERIC(30,6) NOT NULL,
    previous_average_cost NUMERIC(30,12) NOT NULL,
    resulting_average_cost NUMERIC(30,12) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    source_type VARCHAR(64) NOT NULL,
    source_reference_id VARCHAR(128) NULL,
    occurred_at TIMESTAMPTZ NOT NULL,
    correlation_id UUID NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT stock_valuation_movement_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT stock_valuation_movement_valuation_fk
        FOREIGN KEY (organization_id, stock_valuation_id, store_id, product_id, stock_id)
        REFERENCES inventory_costing.stock_valuation (organization_id, id, store_id, product_id, stock_id) ON DELETE RESTRICT,
    CONSTRAINT stock_valuation_movement_stock_movement_fk
        FOREIGN KEY (organization_id, stock_movement_id)
        REFERENCES inventory.stock_movement (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT stock_valuation_movement_type_check
        CHECK (type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'SALE', 'SALE_RETURN')),
    CONSTRAINT stock_valuation_movement_quantity_check
        CHECK (quantity > 0 OR (type = 'OPENING' AND quantity = 0)),
    CONSTRAINT stock_valuation_movement_value_check
        CHECK (unit_cost >= 0 AND value >= 0 AND previous_total_value >= 0 AND resulting_total_value >= 0
            AND previous_average_cost >= 0 AND resulting_average_cost >= 0),
    CONSTRAINT stock_valuation_movement_currency_check CHECK (currency ~ '^[A-Z]{3}$'),
    CONSTRAINT stock_valuation_movement_physical_link_check
        CHECK ((type = 'OPENING' AND stock_movement_id IS NULL) OR (type <> 'OPENING' AND stock_movement_id IS NOT NULL)),
    CONSTRAINT stock_valuation_movement_source_check
        CHECK ((type = 'OPENING' AND source_type = 'BOOTSTRAP')
            OR (type = 'INITIAL_STOCK' AND source_type = 'INITIALIZATION')
            OR (type IN ('ADJUSTMENT_IN', 'ADJUSTMENT_OUT') AND source_type = 'MANUAL_ADJUSTMENT')
            OR (type = 'SALE' AND source_type = 'SALE')
            OR (type = 'SALE_RETURN' AND source_type = 'RETURN')),
    CONSTRAINT stock_valuation_movement_total_check
        CHECK ((type IN ('OPENING', 'INITIAL_STOCK', 'ADJUSTMENT_IN', 'SALE_RETURN')
                AND resulting_total_value = previous_total_value + value)
            OR (type IN ('ADJUSTMENT_OUT', 'SALE')
                AND resulting_total_value = previous_total_value - value)),
    CONSTRAINT stock_valuation_movement_opening_check
        CHECK (type <> 'OPENING' OR (previous_total_value = 0 AND previous_average_cost = 0
            AND (quantity <> 0 OR (unit_cost = 0 AND value = 0))))
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX stock_valuation_movement_physical_unique ON inventory_costing.stock_valuation_movement (organization_id, stock_movement_id) WHERE stock_movement_id IS NOT NULL');
        $this->addSql("CREATE UNIQUE INDEX stock_valuation_movement_opening_unique ON inventory_costing.stock_valuation_movement (organization_id, stock_valuation_id) WHERE type = 'OPENING'");
        $this->addSql('CREATE INDEX stock_valuation_movement_valuation_time_idx ON inventory_costing.stock_valuation_movement (organization_id, stock_valuation_id, occurred_at, id)');
        $this->addSql('CREATE INDEX stock_valuation_movement_store_time_idx ON inventory_costing.stock_valuation_movement (organization_id, store_id, occurred_at, id)');

        $this->addSql('GRANT SELECT, INSERT, UPDATE ON inventory_costing.stock_valuation TO zandu_runtime');
        $this->addSql('GRANT SELECT, INSERT ON inventory_costing.stock_valuation_movement TO zandu_runtime');
        foreach (['inventory_costing.stock_valuation', 'inventory_costing.stock_valuation_movement'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
        }

        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        $this->addSql("CREATE POLICY stock_valuation_tenant_isolation ON inventory_costing.stock_valuation FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
        $this->addSql("CREATE POLICY stock_valuation_movement_tenant_isolation ON inventory_costing.stock_valuation_movement FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inventory_costing.stock_valuation_movement');
        $this->addSql('DROP TABLE inventory_costing.stock_valuation');
        $this->addSql('ALTER TABLE inventory.stock DROP CONSTRAINT stock_costing_identity_unique');
        $this->addSql('DROP SCHEMA inventory_costing');
    }
}
