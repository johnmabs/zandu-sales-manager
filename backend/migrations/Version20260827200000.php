<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist immutable tenant-scoped cost snapshots for completed sale lines';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE sales.sale_line_cost_snapshot (
    organization_id UUID NOT NULL,
    sale_line_id UUID NOT NULL,
    stock_id UUID NOT NULL,
    stock_movement_id UUID NOT NULL,
    quantity NUMERIC(30,12) NOT NULL,
    unit_cost NUMERIC(30,12) NOT NULL,
    total_cost NUMERIC(30,6) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    valuation_version INT NOT NULL,
    occurred_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY (organization_id, sale_line_id),
    CONSTRAINT sale_line_cost_snapshot_line_fk
        FOREIGN KEY (organization_id, sale_line_id)
        REFERENCES sales.sale_line (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT sale_line_cost_snapshot_stock_fk
        FOREIGN KEY (organization_id, stock_id)
        REFERENCES inventory.stock (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT sale_line_cost_snapshot_movement_fk
        FOREIGN KEY (organization_id, stock_movement_id)
        REFERENCES inventory.stock_movement (organization_id, id) ON DELETE RESTRICT,
    CONSTRAINT sale_line_cost_snapshot_quantity_check CHECK (quantity > 0),
    CONSTRAINT sale_line_cost_snapshot_value_check
        CHECK (unit_cost >= 0 AND total_cost >= 0 AND total_cost = ROUND(unit_cost * quantity, 6)),
    CONSTRAINT sale_line_cost_snapshot_currency_check CHECK (currency ~ '^[A-Z]{3}$'),
    CONSTRAINT sale_line_cost_snapshot_version_check CHECK (valuation_version > 0)
)
SQL);
        $this->addSql('CREATE INDEX sale_line_cost_snapshot_movement_idx ON sales.sale_line_cost_snapshot (organization_id, stock_movement_id)');
        $this->addSql('GRANT SELECT, INSERT ON sales.sale_line_cost_snapshot TO zandu_runtime');
        $this->addSql('ALTER TABLE sales.sale_line_cost_snapshot ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE sales.sale_line_cost_snapshot FORCE ROW LEVEL SECURITY');
        $policy = "organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid";
        $this->addSql("CREATE POLICY sale_line_cost_snapshot_tenant_isolation ON sales.sale_line_cost_snapshot FOR ALL TO zandu_runtime USING ($policy) WITH CHECK ($policy)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sales.sale_line_cost_snapshot');
    }
}
