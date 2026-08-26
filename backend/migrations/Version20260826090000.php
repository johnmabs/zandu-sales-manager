<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-scoped inventory stock positions and movement ledger tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization.stores ADD CONSTRAINT store_tenant_id_unique UNIQUE (organization_id, id)');
        $this->addSql(<<<'SQL'
CREATE TABLE inventory.stock (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    store_id UUID NOT NULL,
    product_id UUID NOT NULL,
    quantity_on_hand NUMERIC(30,12) NOT NULL DEFAULT 0,
    initialized BOOLEAN NOT NULL DEFAULT FALSE,
    initialized_at TIMESTAMPTZ NULL,
    initialized_by UUID NULL,
    version INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT stock_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT stock_identity_unique UNIQUE (organization_id, store_id, product_id),
    CONSTRAINT stock_organization_fk FOREIGN KEY (organization_id) REFERENCES organization.organizations (id),
    CONSTRAINT stock_store_fk FOREIGN KEY (organization_id, store_id) REFERENCES organization.stores (organization_id, id),
    CONSTRAINT stock_product_fk FOREIGN KEY (organization_id, product_id) REFERENCES catalog.products (organization_id, id),
    CONSTRAINT stock_quantity_check CHECK (quantity_on_hand >= 0),
    CONSTRAINT stock_initialization_audit_check CHECK ((initialized_at IS NULL) = (initialized_by IS NULL)),
    CONSTRAINT stock_initialization_check CHECK (initialized OR (initialized_at IS NULL AND initialized_by IS NULL)),
    CONSTRAINT stock_version_check CHECK (version > 0)
)
SQL);
        $this->addSql('CREATE INDEX stock_tenant_store_idx ON inventory.stock (organization_id, store_id)');
        $this->addSql('CREATE INDEX stock_tenant_product_idx ON inventory.stock (organization_id, product_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE inventory.stock_movement (
    id UUID NOT NULL,
    organization_id UUID NOT NULL,
    store_id UUID NOT NULL,
    product_id UUID NOT NULL,
    stock_id UUID NOT NULL,
    type VARCHAR(32) NOT NULL,
    quantity NUMERIC(30,12) NOT NULL,
    previous_quantity NUMERIC(30,12) NOT NULL,
    resulting_quantity NUMERIC(30,12) NOT NULL,
    source_type VARCHAR(32) NOT NULL,
    source_reference_id UUID NULL,
    reason VARCHAR(500) NULL,
    performed_by UUID NULL,
    occurred_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT stock_movement_tenant_id_unique UNIQUE (organization_id, id),
    CONSTRAINT stock_movement_stock_fk FOREIGN KEY (organization_id, stock_id) REFERENCES inventory.stock (organization_id, id),
    CONSTRAINT stock_movement_type_check CHECK (type IN ('INITIAL_STOCK', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT')),
    CONSTRAINT stock_movement_source_check CHECK (source_type IN ('INITIALIZATION', 'MANUAL_ADJUSTMENT')),
    CONSTRAINT stock_movement_quantity_check CHECK (quantity > 0),
    CONSTRAINT stock_movement_previous_check CHECK (previous_quantity >= 0),
    CONSTRAINT stock_movement_resulting_check CHECK (resulting_quantity >= 0)
)
SQL);
        $this->addSql('CREATE INDEX stock_movement_tenant_store_product_idx ON inventory.stock_movement (organization_id, store_id, product_id)');
        $this->addSql('CREATE INDEX stock_movement_stock_occurred_idx ON inventory.stock_movement (organization_id, stock_id, occurred_at)');

        foreach (['inventory.stock', 'inventory.stock_movement'] as $table) {
            $this->addSql(sprintf('GRANT SELECT, INSERT, UPDATE, DELETE ON %s TO zandu_runtime', $table));
            $this->addSql(sprintf('ALTER TABLE %s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $table));
        }

        $this->addSql(<<<'SQL'
CREATE POLICY stock_tenant_isolation ON inventory.stock
    FOR ALL TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
        $this->addSql(<<<'SQL'
CREATE POLICY stock_movement_tenant_isolation ON inventory.stock_movement
    FOR ALL TO zandu_runtime
    USING (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
    WITH CHECK (organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inventory.stock_movement');
        $this->addSql('DROP TABLE inventory.stock');
        $this->addSql('ALTER TABLE organization.stores DROP CONSTRAINT store_tenant_id_unique');
    }
}
