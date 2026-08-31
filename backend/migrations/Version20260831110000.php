<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist tenant-owned stock transfers and draft lines';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE inventory.stock_transfer (
 id UUID NOT NULL, organization_id UUID NOT NULL, source_store_id UUID NOT NULL, destination_store_id UUID NOT NULL,
 status VARCHAR(16) NOT NULL, created_by UUID NOT NULL, created_at TIMESTAMPTZ NOT NULL,
 shipped_by UUID NULL, shipped_at TIMESTAMPTZ NULL, cancellation_reason VARCHAR(500) NULL,
 cancelled_by UUID NULL, cancelled_at TIMESTAMPTZ NULL, version INT NOT NULL, PRIMARY KEY (id),
 CONSTRAINT stock_transfer_tenant_unique UNIQUE (organization_id,id),
 CONSTRAINT stock_transfer_source_fk FOREIGN KEY (organization_id,source_store_id) REFERENCES organization.stores (organization_id,id),
 CONSTRAINT stock_transfer_destination_fk FOREIGN KEY (organization_id,destination_store_id) REFERENCES organization.stores (organization_id,id),
 CONSTRAINT stock_transfer_stores_check CHECK (source_store_id <> destination_store_id),
 CONSTRAINT stock_transfer_status_check CHECK (status IN ('DRAFT','SHIPPED','RECEIVED','CANCELLED')),
 CONSTRAINT stock_transfer_version_check CHECK (version > 0)
)
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE inventory.stock_transfer_line (
 id UUID NOT NULL, organization_id UUID NOT NULL, stock_transfer_id UUID NOT NULL, product_id UUID NOT NULL,
 requested_quantity NUMERIC(30,12) NOT NULL, shipped_quantity NUMERIC(30,12) NULL, received_quantity NUMERIC(30,12) NULL,
 PRIMARY KEY (id), CONSTRAINT stock_transfer_line_tenant_unique UNIQUE (organization_id,id),
 CONSTRAINT stock_transfer_line_product_unique UNIQUE (organization_id,stock_transfer_id,product_id),
 CONSTRAINT stock_transfer_line_transfer_fk FOREIGN KEY (organization_id,stock_transfer_id) REFERENCES inventory.stock_transfer (organization_id,id),
 CONSTRAINT stock_transfer_line_product_fk FOREIGN KEY (organization_id,product_id) REFERENCES catalog.products (organization_id,id),
 CONSTRAINT stock_transfer_line_requested_check CHECK (requested_quantity > 0),
 CONSTRAINT stock_transfer_line_shipped_check CHECK (shipped_quantity IS NULL OR (shipped_quantity >= 0 AND shipped_quantity <= requested_quantity)),
 CONSTRAINT stock_transfer_line_received_check CHECK (received_quantity IS NULL OR (shipped_quantity IS NOT NULL AND received_quantity >= 0 AND received_quantity <= shipped_quantity))
)
SQL);
        $this->addSql('CREATE INDEX stock_transfer_tenant_status_idx ON inventory.stock_transfer (organization_id,status,created_at,id)');
        $this->addSql('CREATE INDEX stock_transfer_source_idx ON inventory.stock_transfer (organization_id,source_store_id,status,id)');
        $this->addSql('CREATE INDEX stock_transfer_destination_idx ON inventory.stock_transfer (organization_id,destination_store_id,status,id)');
        foreach (['stock_transfer', 'stock_transfer_line'] as $table) {
            $this->addSql(sprintf('GRANT SELECT,INSERT,UPDATE,DELETE ON inventory.%s TO zandu_runtime', $table));
            $this->addSql(sprintf('ALTER TABLE inventory.%s ENABLE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf('ALTER TABLE inventory.%s FORCE ROW LEVEL SECURITY', $table));
            $this->addSql(sprintf("CREATE POLICY %s_tenant_isolation ON inventory.%s FOR ALL TO zandu_runtime USING (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid) WITH CHECK (organization_id=NULLIF(current_setting('app.organization_id',true),'')::uuid)", $table, $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inventory.stock_transfer_line');
        $this->addSql('DROP TABLE inventory.stock_transfer');
    }
}
